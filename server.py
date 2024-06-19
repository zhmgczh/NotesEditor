from http.server import BaseHTTPRequestHandler,HTTPServer
from socketserver import BaseServer
import datetime,requests,json,sqlite3,re
from urllib.parse import quote,unquote,parse_qs
hostName='45.76.17.116'#'localhost'
serverPort=6789#8080
website_base_url='https://static.zh-tw.top'
category_database_url='https://static.zh-tw.top/category_database.js'
database_name='words.db'
check_date=''
entry_database={}
all_words={}
class MyServer(BaseHTTPRequestHandler):
    def __init__(self,request,client_address,server:BaseServer)->None:
        global check_date
        self.global_debug=''
        if datetime.date.today()!=check_date:
            self.daily_job()
            check_date=datetime.date.today()
        super().__init__(request,client_address,server)
    def daily_job(self):
        global entry_database,all_words
        category_database=json.loads(requests.get(category_database_url).text)['一簡多繁辨析']
        entry_database={}
        all_words={}
        for entry in category_database:
            title=entry['title']
            split=title[len('一簡多繁辨析之「'):-1].split('」→「')
            zhengs=split[0].split('、')
            jians=split[1].split('、')
            new_title='、'.join(zhengs)+'→'+'、'.join(jians)
            entry['title']=new_title
            new_all_words=self.get_all_words(entry['description'])
            for zheng in zhengs:
                if zheng not in entry_database:
                    entry_database[zheng]=[]
                entry_database[zheng].append(entry)
                if zheng not in all_words:
                    all_words[zheng]=[]
                all_words[zheng].append(new_all_words)
            for word in all_words:
                self.add_word(word)
        now=datetime.datetime.now()
        date_time=now.strftime('%Y-%m-%d %H:%M:%S (GMT)')
        self.global_debug='Daily job was finished at '+date_time+'.'
    def print_index(self):
        global entry_database
        self.send_response(200)
        self.send_header('Content-type','text/html;charset=utf-8')
        self.end_headers()
        self.wfile.write(bytes('<html><head><title>編輯《大陸居民臺灣正體字講義》</title></head>','utf-8'))
        self.wfile.write(bytes('<body style="text-align:center">','utf-8'))
        self.wfile.write(bytes('<h1>編輯《大陸居民臺灣正體字講義》</h1>','utf-8'))
        self.wfile.write(bytes('<h2>加入詞彙</h2>','utf-8'))
        self.wfile.write(bytes('<form action="/">','utf-8'))
        self.wfile.write(bytes('<input type="text" id="word" name="word"><br><br>','utf-8'))
        self.wfile.write(bytes('<input type="submit"><br><br>','utf-8'))
        self.wfile.write(bytes('<input type="button" onclick="javascript:location.href=\'/\'+encodeURIComponent(document.getElementById(\'word\').value)+\'/\'" value="分析"></input>','utf-8'))
        self.wfile.write(bytes('</form>','utf-8'))
        self.wfile.write(bytes('<h2>待考詞彙表</h2>','utf-8'))
        conn=sqlite3.connect(database_name)
        cursor=conn.cursor()
        cursor.execute('select word,character from words where checked=false order by character,word;')
        output=cursor.fetchall()
        display={}
        for row in output:
            for entry in entry_database[row[1]]:
                if entry['title'] not in display:
                    display[entry['title']]=[]
                display[entry['title']].append(row[0])
        for title in display:
            self.wfile.write(bytes('<h3>'+title+'</h3>','utf-8'))
            for word in display[title]:
                self.wfile.write(bytes('<p><a href="/'+quote(word)+'/" target="_blank">'+word+'</a></p>','utf-8'))
        conn.close()
        self.wfile.write(bytes('<h3>'+str(self.global_debug)+'</h3>','utf-8'))
        self.wfile.write(bytes('</body></html>','utf-8'))
    def get_all_words(self,article):
        words=[]
        mode=0
        new_word=''
        for i in range(len(article)):
            if 0==mode:
                if '「'==article[i]:
                    mode=1
            elif 1==mode:
                if '（'==article[i]:
                    mode+=1
                elif '」'==article[i]:
                    words.append(new_word)
                    new_word=''
                    mode=0
                else:
                    new_word+=article[i]
            else:
                if '）'==article[i]:
                    mode-=1
                elif '（'==article[i]:
                    mode+=1
        if ''!=new_word:
            words.append(new_word)
        return words
    def get_checked(self,word,character):
        global all_words
        checked=True
        for entry in all_words[character]:
            checked=checked and word in entry
        return checked
    def add_word(self,word):
        global entry_database
        conn=sqlite3.connect(database_name)
        cursor=conn.cursor()
        insert_pairs=[]
        for character in word:
            if character in entry_database:
                insert_pairs.append((word,character))
        for pair in insert_pairs:
            cursor.execute("insert or ignore into words (word,character,checked) values ('"+pair[0]+"','"+pair[1]+"',"+('true' if self.get_checked(pair[0],pair[1]) else 'false')+');')
        conn.commit()
        conn.close()
    def get_search_color(self,article,word):
        colored=[False for _ in article]
        for length in range(len(word),1,-1):
            for i in range(len(word)-length+1):
                truncated_word=word[i:i+length]
                start_indices=[m.start() for m in re.finditer(truncated_word,article)]
                for start_index in start_indices:
                    for j in range(start_index,start_index+length):
                        colored[j]=True
        new_article=''
        for i in range(len(article)):
            if colored[i]:
                new_article+='<span style="background-color:yellow">'+article[i]+'</span>'
            else:
                new_article+=article[i]
        return new_article
    def print_word(self,word):
        global entry_database
        self.send_response(200)
        self.send_header('Content-type','text/html;charset=utf-8')
        self.end_headers()
        conn=sqlite3.connect(database_name)
        cursor=conn.cursor()
        cursor.execute("select character,checked from words where word='"+word+"' order by checked,character;")
        output=cursor.fetchall()
        display={}
        for row in output:
            display[row[0]]=row[1]
        conn.close()
        colored_word=''
        ordered_characters=[]
        for character in word:
            if character in display:
                colored_word+='<span style="background-color:yellow">'+character+'</span>'
                if character not in ordered_characters:
                    ordered_characters.append(character)
            else:
                colored_word+=character
        self.wfile.write(bytes('<html><head><title>'+word+' - 編輯《大陸居民臺灣正體字講義》</title></head>','utf-8'))
        self.wfile.write(bytes('<body style="text-align:center">','utf-8'))
        self.wfile.write(bytes('<h1>編輯《大陸居民臺灣正體字講義》</h1>','utf-8'))
        self.wfile.write(bytes('<h2>'+colored_word+'</h2>','utf-8'))
        self.wfile.write(bytes('<p><a href="/next/">查看下一待考詞彙</a></p>','utf-8'))
        for character in ordered_characters:
            self.wfile.write(bytes('<h3>'+character+'</h3>','utf-8'))
            self.wfile.write(bytes('<input type="button" onclick="javascript:location.href=\'/check/?word='+quote(word)+'&character='+quote(character)+'\'" value="'+('已確認' if display[character] else '未確認')+'"></input>','utf-8'))
            for entry in entry_database[character]:
                self.wfile.write(bytes('<h4 style="color:red">'+entry['title']+'</h4>','utf-8'))
                article='<p>'+self.get_search_color(entry['description'],word).replace('\n','</p><p>')+'</p>'
                self.wfile.write(bytes(article,'utf-8'))
                self.wfile.write(bytes('<p><a href="'+website_base_url+entry['url']+'" target="_blank">轉到 '+entry['title']+'</a></p>','utf-8'))
        self.wfile.write(bytes('<iframe src="https://www.moedict.tw/'+quote(word)+'" height="350px" width="95%" data-ruffle-polyfilled=""></iframe>','utf-8'))
        self.wfile.write(bytes('<p><a href="https://dict.revised.moe.edu.tw/search.jsp?md=1&word='+quote(word)+'&qMd=0&qCol=1" target="_blank">'+word+'</a></p>','utf-8'))
        self.wfile.write(bytes('<h3>'+str(self.global_debug)+'</h3>','utf-8'))
        self.wfile.write(bytes('</body></html>','utf-8'))
    def get_next_word(self):
        global entry_database
        conn=sqlite3.connect(database_name)
        cursor=conn.cursor()
        cursor.execute('select word,character from words where checked=false order by character,word;')
        output=cursor.fetchall()
        temporary={}
        for row in output:
            for entry in entry_database[row[1]]:
                if entry['title'] not in temporary:
                    temporary[entry['title']]=[]
                temporary[entry['title']].append(row[0])
        next_word=None
        for title in temporary:
            next_word=temporary[title][0]
            break
        return next_word
    def check_word_character(self,word,character):
        conn=sqlite3.connect(database_name)
        cursor=conn.cursor()
        cursor.execute("update or ignore words set checked=not checked where word='"+word+"' and character='"+character+"';")
        conn.commit()
        conn.close()
    def redirect(self,url):
        self.send_response(200)
        self.send_header('Content-type','text/html;charset=utf-8')
        self.end_headers()
        self.wfile.write(bytes('<html><head><meta http-equiv="Refresh" content="0; URL='+url+'"/></head></html>','utf-8'))
    def print_404(self):
        pass
    def do_GET(self):
        global check_date
        if '/'==self.path:
            self.print_index()
        elif self.path.startswith('/?word='):
            self.add_word(unquote(self.path[len('/?word='):]))
            self.redirect('/')
        elif self.path.startswith('/check/?'):
            query=parse_qs(self.path[len('/check/?'):])
            if 'word' in query and 'character' in query:
                self.check_word_character(query['word'][0],query['character'][0])
                self.redirect('/'+quote(query['word'][0])+'/')
        elif '/next/'==self.path:
            next_word=self.get_next_word()
            self.redirect('/'+(next_word+'/' if None!=next_word else ''))
        elif '/'==self.path[0] and '/'==self.path[-1] and 0==self.path[1:-1].count('/'):
            word=unquote(self.path[1:-1])
            self.add_word(word)
            self.print_word(word)
        else:
            self.print_404()
        if datetime.date.today()!=check_date:
            self.daily_job()
            check_date=datetime.date.today()
if __name__=='__main__':
    webServer=HTTPServer((hostName,serverPort),MyServer)
    print('Server started http://%s:%s'%(hostName,serverPort))
    try:
        webServer.serve_forever()
    except KeyboardInterrupt:
        pass
    webServer.server_close()
    print('Server stopped.')