"""HTTP integration checks. Run ONLY against a disposable seeded database.
Usage: python tests/http_smoke.py http://localhost:8085
Creates test directory records and sales; never target a real store database.
"""
import sys, re, urllib.request, urllib.parse, http.cookiejar, base64, os, secrets
base=(sys.argv[1] if len(sys.argv)>1 else 'http://localhost:8085').rstrip('/')
jar=http.cookiejar.CookieJar()
client=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
checks=0
admin_password=os.environ['POS_TEST_PASSWORD']
staff_password=secrets.token_urlsafe(24)
def request(path, data=None, files=None, csrf=True):
    global checks
    if data is not None:
        data=dict(data)
        if csrf:
            page=client.open(base+'/login').read().decode()
            key,token=re.search(r'name="(csrf_\w+)" value="([^"]+)"',page).groups()
            data[key]=token
        if files:
            boundary='PosIntegrationBoundary'
            parts=[]
            for k,v in data.items(): parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{k}"\r\n\r\n{v}\r\n'.encode())
            for k,name,mime,content in files:
                parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{k}"; filename="{name}"\r\nContent-Type: {mime}\r\n\r\n'.encode()+content+b'\r\n')
            body=b''.join(parts)+f'--{boundary}--\r\n'.encode()
            req=urllib.request.Request(base+path,body,{'Content-Type':'multipart/form-data; boundary='+boundary,'Referer':base+path.replace('/save','/new')})
        else: req=urllib.request.Request(base+path,urllib.parse.urlencode(data).encode(),{'Referer':base+path.replace('/save','/new')})
    else: req=base+path
    result=client.open(req)
    return result.geturl(),result.read().decode(errors='replace')
def check(ok,label):
    global checks
    assert ok,label
    checks+=1
    print('PASS:',label)
for path in ['/','/products','/customers','/users','/sales','/sales/new','/products/new','/media/not-an-image']:
    url,_=request(path); check(url.endswith('/login'),'Authentication protects '+path)
_,html=request('/login',{'username':'admin','password':'wrong'})
check('Incorrect username or password.' in html,'Incorrect password rejected')
_,html=request('/login',{'username':'admin','password':admin_password})
check('Overview' in html,'Valid login accepted')
try:
    request('/customers/save',{'full_name':'Forbidden','email':'x@example.com'},csrf=False)
    raise AssertionError('Missing CSRF accepted')
except urllib.error.HTTPError as e: check(e.code==403,'Missing CSRF rejected')
_,html=request('/customers/save',{'full_name':'Invalid','email':'bad'})
check('valid email' in html.lower(),'Invalid email rejected')
_,html=request('/products/save',{'name':'Invalid','price':'1.999','stock_quantity':'-1'})
check('Record saved.' not in html,'Negative stock / excess decimal rejected')
png=base64.b64decode('iVBORw0KGgoAAAANSUhEUgAAAAgAAAAICAIAAABLbSncAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAADElEQVQImWNgGB4AAADIAAH4XVuUAAAAAElFTkSuQmCC')
_,html=request('/products/save',{'name':'HTTP Test Product','price':'12.50','stock_quantity':'4'},[('image','image.png','image/png',png)])
if 'Record saved.' not in html: print(html[:2500])
check('Record saved.' in html,'Product and image created')
pid=re.search(r'products/(\d+)/edit',html).group(1)
image=re.search(r'src="([^"]+/media/[^"]+)"',html).group(1)
img=client.open(image).read();check(img[:2]==b'\xff\xd8','Upload re-encoded to JPEG and served')
_,html=request('/products/save',{'id':pid,'name':'HTTP Edited Product','price':'14.25','stock_quantity':'4'})
check('HTTP Edited Product' in html,'Product updated')
_,html=request('/products/save',{'name':'Bad Upload','price':'1','stock_quantity':'1'},[('image','evil.php','application/x-php',b'<?php echo 1;')])
check('Only JPEG, PNG and WebP' in html,'Executable upload rejected')
_,html=request('/customers/save',{'full_name':'HTTP Customer','email':'http@example.com','phone':'09123456789'})
check('Record saved.' in html,'Customer created'); cid=re.search(r'customers/(\d+)/edit',html).group(1)
_,html=request('/customers/save',{'id':cid,'full_name':'HTTP Edited Customer','email':'edit@example.com','phone':''})
check('HTTP Edited Customer' in html,'Customer updated')
_,html=request('/users/save',{'username':'httptest','full_name':'HTTP Staff','password':staff_password},[('avatar','avatar.png','image/png',png)])
check('Record saved.' in html,'Staff with avatar created'); uid=re.search(r'users/(\d+)/edit',html).group(1)
_,html=request('/users/save',{'id':uid,'username':'httptest','full_name':'HTTP Edited Staff','password':''})
check('HTTP Edited Staff' in html,'Staff updated with password preserved')
_,html=request('/users/save',{'username':'httptest','full_name':'Duplicate','password':staff_password})
check('already in use' in html,'Duplicate username rejected')
_,html=request('/users/1/delete',{})
check('cannot delete your own' in html,'Self-deletion prevented')
_,html=request('/sales',{'product_id':pid,'customer_id':cid,'quantity':'5'})
check('only 4 units' in html,'Oversell rejected')
_,html=request('/sales',{'product_id':pid,'customer_id':cid,'quantity':'2'})
check('Sale recorded and stock updated.' in html and '28.50' in html and 'HTTP Edited Customer' in html,'Sale recorded with correct total and customer')
_,html=request('/products/'+pid+'/edit')
check('name="stock_quantity" value="2"' in html,'Stock reduced exactly once')
_,html=request('/products/'+pid+'/delete',{})
check('used by sales history' in html,'Sold product protected from deletion')
_,html=request('/customers/'+cid+'/delete',{})
check('Record deleted.' in html,'Customer deleted')
_,html=request('/sales');check('Walk-in customer' in html,'Customer deletion preserves history')
_,html=request('/users/'+uid+'/delete',{})
check('Record deleted.' in html,'Unused staff deleted')
_,html=request('/products/save',{'name':'HTTP Delete Product','price':'1','stock_quantity':'1'})
deletepid=re.search(r'products/(\d+)/edit',html).group(1)
_,html=request('/products/'+deletepid+'/delete',{});check('Record deleted.' in html,'Unused product deleted')
url,_=request('/logout',{});check(url.endswith('/login'),'Logout returns to login')
url,_=request('/sales');check(url.endswith('/login'),'Logout revokes protected access')
print(f'{checks} HTTP checks passed.')
