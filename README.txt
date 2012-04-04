HOW TO build yourself The User Guide written for AsciiDoc

NOTE: You should have installed on your system
.For standard HTML or Docbook targets

AsciiDoc 8.6.7
    http://www.methods.co.nz/asciidoc/
Source-Highlight 3.1+
    http://www.gnu.org/software/src-highlite/
or
Pygments 1.3.1+
    http://pygments.org/

.For PDF target
DocBook to LaTeX Publishing
    http://dblatex.sourceforge.net/
or
Apache FOP
    http://xmlgraphics.apache.org/fop/index.html

With AsciiDoc 8.6.7 or greater you need to install additionnal theme first :
$ wget http://growl.laurent-laville.org/asciidoc-themes/growl-1.0.zip
$ asciidoc-8.6.7/asciidoc.py --theme install growl-1.0.zip
    
With external http://growl.laurent-laville.org/ layout, and linked javascript and styles
$ asciidoc-8.6.7/asciidoc.py
  -a icons
  -a toc2
  -a linkcss
  -a theme=growl
  -n
  -v
  docs/userguide.txt

With basic layout, and embbeded javascript and styles
$ asciidoc-8.6.7/asciidoc.py
  -a icons
  -a toc
  -n
  -v
  docs/userguide.txt

Or used Phing 2.4.11

But be careful to change first properties 'asciidoc.home' and 'homedir' values 
that reflect your platform and installation.

phing  /path/to/build-phing.xml -Dasciidoc.home=? -Dhomedir=?

Since version 2.5.0 you can use alternative solution: use a properties file that define
all values you wan't to overload (example)

phing  /path/to/build-phing.xml -Ddefault.properties=/path/to/your-local.properties


Single Html file
phing  /path/to/build-phing.xml  make-userguide

Many Html files
phing  /path/to/build-phing.xml  make-userguide-chunked

Microsoft Html Help file (chm format)
phing  /path/to/build-phing.xml  make-userguide-htmlhelp

PDF file (with FOP)
Since version 2.6.0 you can generate either PDF in A4 or US format

phing  /path/to/build-phing.xml  make-userguide-pdf-a4
or
phing  /path/to/build-phing.xml  make-userguide-pdf-us
