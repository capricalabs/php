#!/usr/bin/env python
# -*- coding: UTF-8 -*-

import math
import nltk
from nltk.corpus import wordnet as wn
import MySQLdb
import re, collections
import cgi

f = open( "../settings_db.inc", "r" )
conf = f.read()
f.close()
conf = re.findall( r'\$SQL_.*?=\s*"(.*)";', conf )
db = MySQLdb.connect(host=conf[0],user=conf[1],passwd=conf[2],db=conf[3])

# SPELL CORRECTOR
def words(text): return re.findall('[a-z]+', text.lower()) 

def train(features):
  model = collections.defaultdict(lambda: 1)
  for f in features:
    model[f] += 1
  return model

NWORDS = train(words(file('spell_corrector.txt').read()))

alphabet = 'abcdefghijklmnopqrstuvwxyz'

def edits1(word):
  splits     = [(word[:i], word[i:]) for i in range(len(word) + 1)]
  deletes    = [a + b[1:] for a, b in splits if b]
  transposes = [a + b[1] + b[0] + b[2:] for a, b in splits if len(b)>1]
  replaces   = [a + c + b[1:] for a, b in splits for c in alphabet if b]
  inserts    = [a + c + b     for a, b in splits for c in alphabet]
  return set(deletes + transposes + replaces + inserts)

def known_edits2(word):
  return set(e2 for e1 in edits1(word) for e2 in edits1(e1) if e2 in NWORDS)

def known(words): return set(w for w in words if w in NWORDS)

def correct(word):
  candidates = known([word]) or known(edits1(word)) or known_edits2(word) or [word]
  return max(candidates, key=NWORDS.get)
# END SPELL CORRECTOR

wn_size = 117659
sub_tresh = 0.42 # below what to count irrelevant
cat_tresh = 0.68 # above what to place in one category

print "Content-Type: text/html;charset=utf-8"
print

form = cgi.FieldStorage()
if "booklet_id" not in form or "step_id" not in form:
  print "<H1>Error</H1>"
  print "Invalid parameters received."
  exit()

print "<pre>"

# get subject and ideas from db
booklet_id = form["booklet_id"].value
step_id = form["step_id"].value
c=db.cursor()
c.execute("""SELECT subject_for_auto_evaluation FROM steps
          WHERE id = %s""", (step_id,))
subject = c.fetchone()
if not subject or not subject[0]:
  print "You have not defined the subject for this step to test against. Please contact admin to define a subject for this step."
  print "</pre>"
  exit()
else:
  subject = subject[0]
c.execute("""SELECT Text FROM TeamWork
          WHERE booklet_id = %s and step_id = %s""", (booklet_id, step_id))
ideas = c.fetchall()
ideas = map( lambda(el): el[0], ideas )

# store common root nodes that are slower to process and cache what we find during calculations
hypo_cache = {
  'entity.n.01': 96308,
  'abstraction.n.06': 41647,
  'relation.n.01': 5596,
  'measure.n.02': 2398,
  'attribute.n.02': 7585,
  'psychological_feature.n.01': 13030,
  'communication.n.02': 4760,
  'physical_entity.n.01': 54651,
  'causal_agent.n.01': 9331,
  'object.n.01': 32983,
  'thing.n.12': 2416,
  'process.n.06': 1676,
  'matter.n.03': 8243,
  'natural_object.n.01': 1197,
  'living_thing.n.01': 17930,
  'artifact.n.01': 12011,
  'location.n.01': 1278,
  'whole.n.02': 31146,
  'organism.n.01': 17764,
  'plant.n.02': 4700,
  'animal.n.01': 4357,
  'person.n.01': 7982,
  'instrumentality.n.03': 6117,
  'structure.n.01': 1482,
  'creation.n.02': 655,
  'covering.n.02': 1037,
  'commodity.n.01': 703
}
ic_cache = {}

# returns number of hyponyms in the tree
def hypo_length( syns ):
  global hypo_cache
  length = hypo_cache.get( syns.name )
  if length:
    return length
  length = 0
  list = [syns]
  while len(list):
    elem = list.pop()
    length += 1
    for hypo in elem.hyponyms():
      list.append( hypo )
  hypo_cache[syns.name] = length
  #print "hypo_length( {0} ) = {1}".format( syns.name, length )
  return length

# calculate IC based on wn hyponyms count and store in cache
def ic( syns ):
  global wn_size, ic_cache
  res = ic_cache.get( syns.name )
  if res == None:
    res = 1 - math.log10( hypo_length( syns ) ) / math.log10( wn_size )
    ic_cache[syns.name] = res
  #print "IC( {0} ) = {1}".format( syns.name, res )
  return res

# calculate semantic similarity using Jiang and Conrath formula
def ss_pair( syns1, syns2 ):
  # get lowest common hypernym
  lch = syns1.lowest_common_hypernyms( syns2 )
  if len( lch ) == 0:
    #print "LCH( {0}, {1} ) = NONE".format( syns1.name, syns2.name )
    #print "SS: {0 }\n".format( 0 )
    return 0
  lch = lch[0]
  #print "LCH( {0}, {1} ) = {2}".format( syns1.name, syns2.name, lch.name )
  # calculate semantic similarity using Jiang and Conrath formula
  ss = 1 - ( ic( syns1 ) + ic( syns2 ) - 2 * ic( lch ) ) / 2
  #print "SS: {0}\n".format( ss )
  return ss

# tokenize words and return nouns+verbs only
def tkn( txt ):
  words = nltk.word_tokenize( txt )
  if len( words ) > 1:
    words = filter( lambda(el): ( el[1].startswith('NN') or el[1].startswith('VB') ), nltk.pos_tag( words ) )
    return map( lambda(el): el[0], words )
  else:
    return words

def compare_ideas( idea, subject ):
  idea_words = tkn( idea )
  avgs = []
  for idea_word in idea_words:
    # spellcheck
    idea_word = correct( idea_word )
    ss_pairs = [ss_pair( s, i )
      for i in wn.synsets( idea_word )
      for subject_word in tkn( subject )
      for s in wn.synsets( correct( subject_word ) )]
    if len( ss_pairs ) > 0:
      ss = max( ss_pairs )
    else:
      ss = 0
    #print "\t\t\t\tSS( {0}, {1} ) = {2}\n".format( idea_word, subject, ss )
    avgs.append( ss )
  avg = float(sum(avgs))/len(avgs) if len(avgs) > 0 else float('nan')
  #print "\t\tSS( {0}, {1} ) = {2}\n".format( idea, subject, avg )
  return avg

relevant_ideas = []

for idea in ideas:
  sim = compare_ideas( idea, subject )
  print idea
  if sim >= sub_tresh:
    print 'RELEVANT'
  else:
    print 'IRRELEVANT'
  relevant_ideas.append( idea )

pairs = []
for i in range(0,len(relevant_ideas)):
  for k in range(i+1,len(relevant_ideas)):
    sim = compare_ideas( relevant_ideas[i], relevant_ideas[k] )
    pairs.append( { 'idea1': relevant_ideas[i], 'idea2': relevant_ideas[k], 'sim': sim } )

categories = []
categorized_ideas = set()
for pair in sorted( pairs, key = lambda pair: 1-pair['sim'] ):
  if pair['sim'] >= cat_tresh:
    print "SS( {0}, {1} ) = {2}".format( pair['idea1'], pair['idea2'], pair['sim'] )
    # this pair contains ideas from the same category - place them in the special list
    added = False
    for i in range(0,len(categories)):
      if pair['idea1'] in categories[i] and pair['idea2'] not in categorized_ideas:
        categories[i].add( pair['idea2'] )
        categorized_ideas.add( pair['idea2'] )
        added = True
      if pair['idea2'] in categories[i] and pair['idea1'] not in categorized_ideas:
        categories[i].add( pair['idea1'] )
        categorized_ideas.add( pair['idea1'] )
        added = True
    if not added and pair['idea1'] not in categorized_ideas and pair['idea2'] not in categorized_ideas:
      categories.append( set( [ pair['idea1'], pair['idea2'] ] ) )
      categorized_ideas.add( pair['idea1'] )
      categorized_ideas.add( pair['idea2'] )

for idea in relevant_ideas:
  if idea not in categorized_ideas:
    categories.append( set( [ idea ] ) )

print "Number of categories: {0}".format( len( categories ) )
for idea_set in categories:
  print "({0})".format( ','.join( idea_set ) )

print "</pre>"