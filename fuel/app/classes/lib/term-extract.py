#buildkeywords.py
try:
	import json
except ImportError:
	import simplejson as json
import sys
from topia.termextract import tag
from topia.termextract import extract
 
#set path=%path%;c:python27
#http://pypi.python.org/pypi/topia.termextract
#http://www.peterbe.com/plog/uniqifiers-benchmark
 
def uniqify(seq, idFun=None):
    # order preserving
    if idFun is None:
        def idFun(x): return x
    seen = {}
    result = []
    for item in seq:
        marker = idFun(item)
        # in old Python versions:
        # if seen.has_key(marker)
        # but in new ones:
        if marker in seen: continue
        seen[marker] = 1
        result.append(item)
    return result
 
def build(language='english'):
    # initialize the tagger with the required language
    tagger = tag.Tagger(language)
    tagger.initialize()
 
    # create the extractor with the tagger
    extractor = extract.TermExtractor(tagger=tagger)
    # invoke tagging the text
    extractor.tagger(sys.argv[1])
    # extract all the terms, even the &amp;quot;weak&amp;quot; ones
    extractor.filter = extract.DefaultFilter(singleStrengthMinOccur=1)
    # extract
    return extractor(sys.argv[1])
 
resultList = []

# get a results
result = build('english')
# or result = build('dutch')
 
for r in result:
    # discard the weights for now, not using them at this point and defaulting to lowercase keywords/tags
    resultList.append(r[0])
 
# dump to JSON output
print json.dumps(sorted(uniqify(resultList)))
