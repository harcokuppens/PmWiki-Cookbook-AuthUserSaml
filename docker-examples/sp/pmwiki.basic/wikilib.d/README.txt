generate wiki pages
--------------------

Use './create_wikipage.bash' script to generate pmwiki page file from a text
file containing pmwiki markdown.

We used this to create custom pmwiki pages for the pages:
 * Site.AuthForm 
 * Site.PageActions

info create_wikipage.bash
--------------------------

$ ./create_wikipage.bash
usage:
  create_wikipage.bash  INPUTFILE OUTPUTFILE
params:
  INPUTFILE: text file with pmwiki markdown for wiki page
  OUTPUTFILE: wiki page to be included in wiki.d or wikilib.d
