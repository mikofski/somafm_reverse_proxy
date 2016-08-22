#! /usr/bin/env python

from flup.server.fcgi import WSGIServer
import os
import requests
import bs4
import logging

dirname = os.path.dirname(os.path.abspath(__file__))
logging.basicConfig(filename=os.path.join(dirname, 'somafm.log'))
logger = logging.getLogger(__name__)
logger.setLevel(logging.DEBUG)
logger.debug('dirname: %s', dirname)

def app(environ, start_response):
    logger.debug('environ:\n%r', environ)
    start_response('200 OK', [('Content-Type', 'text/html')])

    url = 'http://somafm.com/indiepop/songhistory.html'
    r = requests.get(url)
    logger.debug('response: %s', r)
    #logger.debug('content:\n%s', r.content)

    dom = bs4.BeautifulSoup(r.content)
    #logger.debug('dom:\n%s', dom)

    table = dom.find('div', id='playinc').find('table')
    rows = table.find_all('tr')
    colheaders = [col.text for col in rows[0].find_all('td')]
    data = [
        [col.text for col in rows[r].find_all('td')] for r in xrange(2, len(rows))
    ]
    with open(os.path.join(dirname, '..', 'www', 'index.html'), 'r') as f:
        index = bs4.BeautifulSoup(f.read())
    logger.debug('index:\n%r', index)

    new_table = index.new_tag('table')
    new_table['class'] = 'table table-striped'

    new_head = index.new_tag('thead')
    new_row = index.new_tag('tr')
    for c in colheaders:
        new_data = index.new_tag('th')
        new_data.string = c
        new_row.append(new_data)
    new_head.append(new_row)
    new_table.append(new_head)

    new_body = index.new_tag('tbody')
    for r in data:
        new_row = index.new_tag('tr')
        for d in r:
            new_data = index.new_tag('td')
            new_data.string = d
            new_row.append(new_data)
        new_body.append(new_row)
    new_table.append(new_body)

    logger.debug('index.body.contents')
    jumbotron = index.body.contents[5]
    new_div = index.new_tag('div')
    new_div['class'] = 'container'
    new_h3 = index.new_tag('h3')
    new_a = index.new_tag('a')
    new_a['href'] = '/songhistory/indiepop'
    new_a.string = 'Indie Pop Rocks!'
    new_h3.append(new_a)
    new_div.append(new_h3)
    new_div.append(new_table)
    jumbotron.insert_after(new_div)

    html = index.prettify().encode('utf-8')

    with open(os.path.join(dirname, '..', 'www', 'somafm.html'), 'w') as f:
        logger.debug('somafm.html written to file: %r', f)
        f.write(html)

    return ['%s\n' % line for line in html.split('\n')]

if __name__ == '__main__':
    WSGIServer(app).run()
