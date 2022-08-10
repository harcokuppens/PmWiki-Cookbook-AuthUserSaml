#!/bin/bash
set -eE

USAGE="usage:\n  create_wikipage.bash  INPUTFILE OUTPUTFILE\nparams:\n  INPUTFILE: text file with pmwiki markdown for wiki page\n  OUTPUTFILE: wiki page to be included in wiki.d or wikilib.d\n"
if [[ "$#" != "2" ]] ; then
  printf "$USAGE"
  exit 0
fi

inputfile="$1"
outputfile="$2"

# https://www.pmwiki.org/wiki/PmWiki/PageFileFormat
# Only two lines are necessary in a PmWiki page file:
#
#    version=pmwiki-2.1.0 urlencoded=1
#    text=Markup text
#
# "version=" tells PmWiki that the values are urlencoded. The actual value doesn't matter,
# as long as "urlencoded=1" appears somewhere in the line.
#
# "text=" needs to have the markup text with newlines converted to "%0a" and percent signs converted to "%25".
#
# In addition, PmWiki writes pages with '<' encoded as "%3c" (to help with security),
# but it doesn't require that <'s be encoded that way in order to be able to read the page.
#  More conversions are possible to be added in the future.



PMWIKI_VERSION="2.3.7"
printf "version=pmwiki-${PMWIKI_VERSION} urlencoded=1\ntext="  > "$outputfile" 
cat "$inputfile"   | sed 's/%/%25/g' | sed 's/</%3c/g'  | tr '\n' '\0'  | sed 's/\x0/%0a/g' >> "$outputfile"
