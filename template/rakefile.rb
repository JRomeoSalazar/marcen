require 'coyote/rake'

coyote :build do |config|
  config.input = "public/less/frontend.less"
  config.output = "public/css/frontend.css"
end