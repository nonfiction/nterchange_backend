require 'rubygems'
require 'bundler'
require 'pathname'
require 'logger'
require 'fileutils'
Bundler.require

require 'active_record'
require 'yui/compressor'
require 'uglifier'

dir = Rake.application.original_dir

ROOT        = Pathname(File.dirname(__FILE__))
BUNDLES     = %w( surftoedit.js surftoedit.css jquery-ui.js noty.js )
BUILD_DIR   = ROOT.join("nterchange/assets")
SOURCE_DIR  = ROOT.join("app/assets")
COMPONENTS_DIR  = ROOT.join("nterchange/assets/components")

namespace :asset do
  desc "Compile all assets"
  task :compile do
    Sprockets::Cache::FileStore.new(File.join ROOT, '.cache')
    sprockets = Sprockets::Environment.new(ROOT) do |env|
      env.logger = Logger.new(STDOUT)
    end

    # sprockets.css_compressor = YUI::CssCompressor.new
    # sprockets.js_compressor  = Uglifier.new(:mangle => false)

    sprockets.append_path(SOURCE_DIR.join('javascripts').to_s)
    sprockets.append_path(SOURCE_DIR.join('stylesheets').to_s)
    sprockets.append_path(COMPONENTS_DIR.to_s)

    BUNDLES.each do |bundle|
      asset = sprockets.find_asset(bundle)
      prefix, basename = asset.pathname.to_s.split('/')[-2..-1]
      FileUtils.mkpath BUILD_DIR.join(prefix)
      realname = asset.pathname.basename.to_s.split(".")[0..1].join(".")
      output_file = BUILD_DIR.join(prefix, realname)

      File.open(output_file, 'wb') do |f|
        f.write asset.to_s
      end
    end

    # # Make available bower components in public_html
    # FileUtils.cp_r(Dir["#{SOURCE_DIR}/components*"],BUILD_DIR)
  end
end
