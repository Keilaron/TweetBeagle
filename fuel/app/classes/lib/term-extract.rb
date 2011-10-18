require "rubygems"
require "term-extract"
require "json"

puts TermExtract.extract(ARGV[0].dup).to_json