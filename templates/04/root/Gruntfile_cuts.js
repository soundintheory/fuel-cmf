module.exports = function(grunt) {

  //Initializing the configuration object
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),

    // Concatenate and compress JS
    uglify: {

      // Vendor libraries
      vendor: {
        options: {
          mangle: false,
          sourceMap: true,
          sourceMapName: 'public/assets/js/dist/vendor.min.map'
        },
        files: {
          'public/assets/js/dist/vendor.min.js': [

            // Core libs
            'bower_components/html5shiv/dist/html5shiv.js',
            'bower_components/respond/dest/respond.src.js',
            'bower_components/eventEmitter/EventEmitter.js',
            'bower_components/jquery/dist/jquery.js',

            // Bootstrap JS
            'bower_components/bootstrap-sass/assets/javascripts/bootstrap/affix.js',
            'bower_components/bootstrap-sass/assets/javascripts/bootstrap/alert.js',
            //'bower_components/bootstrap-sass/assets/javascripts/bootstrap/button.js',
            //'bower_components/bootstrap-sass/assets/javascripts/bootstrap/carousel.js',
            'bower_components/bootstrap-sass/assets/javascripts/bootstrap/collapse.js',
            'bower_components/bootstrap-sass/assets/javascripts/bootstrap/dropdown.js',
            //'bower_components/bootstrap-sass/assets/javascripts/bootstrap/modal.js',
            //'bower_components/bootstrap-sass/assets/javascripts/bootstrap/popover.js',
            //'bower_components/bootstrap-sass/assets/javascripts/bootstrap/scrollspy.js',
            //'bower_components/bootstrap-sass/assets/javascripts/bootstrap/tab.js',
            //'bower_components/bootstrap-sass/assets/javascripts/bootstrap/tooltip.js',
            'bower_components/bootstrap-sass/assets/javascripts/bootstrap/transition.js',

            // Other JS libs
            'bower_components/parsleyjs/dist/parsley.js',
            'bower_components/jquery.easing/js/jquery.easing.js',
            'bower_components/jquery-placeholder/jquery.placeholder.js',
            'bower_components/imagesloaded/imagesloaded.js',
            'bower_components/magnific-popup/dist/jquery.magnific-popup.js',
            'bower_components/FlexSlider/jquery.flexslider.js'
          ]
        }
      },

      // Custom code
      core: {
        options: {
          mangle: false,
          sourceMap: true,
          sourceMapName: 'public/assets/js/dist/core.min.map',
          banner: '/*! <%= pkg.name %> - v<%= pkg.version %> - <%= grunt.template.today("yyyy-mm-dd") %> */'
        },
        files: {
          'public/assets/js/dist/core.min.js': [
            'public/assets/js/core.js'
          ],
        }
      }
      
    },

    // Cache bust
    cacheBust: {
      options: {
        encoding: 'utf8',
        algorithm: 'md5',
        length: 16,
        rename: false,
        enableUrlFragmentHint: true
      },
      assets: {
        files: [{
          baseDir: 'public/',
          src: [
            'fuel/app/views/**/*.twig',
            'fuel/app/views/**/*.html',
            'fuel/app/views/**/*.php'
          ]
        }]
      }
    },

    // Build sprite file
    sprite: {
      all: {
        src: 'public/assets/images/sprites/**/*.png',
        dest: 'public/assets/images/sprite.png',
        destCss: 'public/assets/scss/global/utilities/_sprites.scss',
        cssTemplate: 'public/assets/scss/global/utilities/sprites.template.mustache',
        padding: 2,
        algorithm: 'binary-tree'
      }
    },

    // Copy vendor files
    copy: {
      fonts: {
        expand: true,
        src: [
          'bower_components/fontawesome/fonts/**/*.{eot,svg,ttf,woff,woff2}',
          'bower_components/FlexSlider/fonts/**/*.{eot,svg,ttf,woff,woff2}',
          'bower_components/bootstrap-sass/**/*.{eot,svg,ttf,woff,woff2}'
        ],
        dest: 'public/assets/fonts/',
        flatten: true,
        filter: 'isFile',
      },
    },

    // Concatenate and minify CSS
    cssmin: {
      vendor: {
        options: {
          sourceMap: true
        },
        files: {
          'public/assets/css/vendor.min.css': [
            'bower_components/FlexSlider/flexslider.css',
            'bower_components/magnific-popup/dist/magnific-popup.css',
            'bower_components/fontawesome/css/font-awesome.css',

          ]
        }
      }
    },

    // Watch task
    watch: {
      options: {
        livereload: true
      },
      templates: {
        files: ['public/cuts/**/*.php', 'public/cuts/**/*.html', 'fuel/app/views/**/*.twig'],
      },
      sprite: {
        files: ['public/assets/images/sprites/**/*.png'],
        tasks: ['sprite']
      },
      vendorjs: {
        files: ['bower_components/**/*.js'],
        tasks: ['uglify:vendor']
      },
      corejs: {
        files: ['public/assets/js/**/*.js', '!public/assets/js/dist/*.js'],
        tasks: ['uglify:core']
      },
      vendorfonts: {
        files: ['public/components/**/*.{eot,svg,ttf,woff,woff2}'],
        tasks: ['copy:fonts']
      },
    }

  });

  // Plugin loading
  grunt.loadNpmTasks('grunt-spritesmith');
  grunt.loadNpmTasks('grunt-contrib-copy');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-contrib-concat');
  grunt.loadNpmTasks('grunt-contrib-cssmin');
  grunt.loadNpmTasks('grunt-contrib-uglify');
  grunt.loadNpmTasks('grunt-sass-globbing');
  grunt.loadNpmTasks('grunt-cache-bust');
  grunt.loadNpmTasks('grunt-hub');

  // Task definition
  grunt.registerTask('dist', ['sprite', 'uglify:vendor', 'uglify:core', 'copy:fonts','cssmin:vendor']);
  grunt.registerTask('dev', ['dist', 'watch']);
  grunt.registerTask('default', ['dev']);

};