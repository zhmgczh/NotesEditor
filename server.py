from http.server import BaseHTTPRequestHandler,HTTPServer
from socketserver import BaseServer
import datetime
hostName='localhost'
serverPort=8080
class MyServer(BaseHTTPRequestHandler):
    def __init__(self,request,client_address,server:BaseServer)->None:
        self.check_date=datetime.date.today()
        super().__init__(request,client_address,server)
    def daily_job(self):
        pass
    def print_index(self):
        self.send_response(200)
        self.send_header('Content-type','text/html;charset=utf-8')
        self.end_headers()
        self.wfile.write(bytes('<html><head><title>編輯《大陸居民臺灣正體字講義》</title></head>','utf-8'))
        self.wfile.write(bytes('<body style="text-align:center">','utf-8'))
        self.wfile.write(bytes('<h1>編輯《大陸居民臺灣正體字講義》</h1>','utf-8'))
        self.wfile.write(bytes('<h2>加入詞彙</h2>','utf-8'))
        self.wfile.write(bytes('<form action="/">','utf-8'))
        self.wfile.write(bytes('<input type="text" name="word"><br><br>','utf-8'))
        self.wfile.write(bytes('<input type="submit">','utf-8'))
        self.wfile.write(bytes('</form>','utf-8'))
        self.wfile.write(bytes('<h2>待考詞彙表</h2>','utf-8'))
        self.wfile.write(bytes('</body></html>','utf-8'))
    def add_word(self,word):
        pass
    def print_word(self,word):
        pass
    def print_404(self):
        pass
    def do_GET(self):
        if '/'==self.path:
            self.print_index()
        elif self.path.startswith('/?word='):
            self.add_word(self.path[len('/?word='):])
            self.print_index()
        elif '/'==self.path[0] and '/'==self.path[-1] and 0==self.path[1:-1].count('/'):
            self.print_word(self.path[1:-1])
        else:
            self.print_404()
        if datetime.date.today()!=self.check_date:
            self.daily_job()
            self.check_date=datetime.date.today()
if __name__=='__main__':
    webServer=HTTPServer((hostName,serverPort),MyServer)
    print('Server started http://%s:%s'%(hostName,serverPort))
    try:
        webServer.serve_forever()
    except KeyboardInterrupt:
        pass
    webServer.server_close()
    print('Server stopped.')