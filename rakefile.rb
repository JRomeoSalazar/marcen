require 'coyote/rake'

coyote :build do |config|
  config.input = "src/Sylius/Bundle/WebBundle/Resources/public/less/frontend.less"
  config.output = "src/Sylius/Bundle/WebBundle/Resources/public/css/frontend.css"
end