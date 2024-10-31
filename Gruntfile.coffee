#global module:false

staticDir = 'postcard-core/'

jsDir = staticDir + 'scripts/'

module.exports = (grunt) ->

  # Project configuration.
  grunt.initConfig({
    # Metadata.
    meta:
      version: '0.1.0'
    banner: '/*! Postcard Grunt - v<%= meta.version %> - ' + '<%= grunt.template.today("yyyy-mm-dd") %>\n' +'* http://kylnew.ca/\n' + '* Copyright (c) <%= grunt.template.today("yyyy") %> ' + 'Kyle Newsome Licensed MIT */\n'
    # Task configuration.
    coffee:
      options:
        banner: '<%= banner %>',
        bare: true
      dist:
        src: [
          jsDir + "postcard.coffee"
        ],
        dest: jsDir + 'postcard.js'
    compass:
      dist:
        options:
          config: "config.rb"

    watch:
      scripts:
        files: ['**/*.coffee']
        tasks: ['coffee']
      css:
        files: ['**/*.sass']
        tasks: ['compass']
    })

  # These plugins provide necessary tasks.
  grunt.loadNpmTasks 'grunt-contrib-coffee'
  grunt.loadNpmTasks 'grunt-contrib-compass'
  grunt.loadNpmTasks 'grunt-contrib-watch'

  # Default task.
  grunt.registerTask 'default', ['coffee', 'compass', 'watch']