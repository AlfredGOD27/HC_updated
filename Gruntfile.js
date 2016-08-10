// See: http://24ways.org/2013/grunt-is-not-weird-and-hard/
module.exports = function(grunt) {

	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),

		clean: {
			build: {
				src: ['build']
			}
		},

		csscomb: {
			options: {
				config: 'sass/.csscomb.json'
			},
			build: {
				expand: true,
				cwd: 'sass/',
				src: ['**/*.scss', '!_mixins.scss'],
				dest: 'sass/'
			}
		},

		sass: {
			options: {
				compass: true,
				style: 'expanded',
				precision: 3,
				sourcemap: 'none'
			},
			build: {
				files: {
					'build/css/style.css': 'sass/style.scss',
					'build/css/profiles.css': 'sass/profiles.scss',
					'build/css/admin.css': 'sass/admin.scss'
				}
			}
		},

		csslint: {
			build: {
				options: {
					csslintrc: 'sass/.csslintrc'
				},
				src: [
					'build/css/style.css',
					'build/css/profiles.css',
					'build/css/admin.css'
				]
			}
		},

		jshint: {
			options: {
				globals: {
					jQuery: true
				}
			},
			build: {
				files: {
					src: ['js/**/*.js']
				}
			}
		},

		concat: {
			build: {
				src: [
					'bower_components/include-media-export/include-media.js',
					'bower_components/iOS-Orientationchange-Fix/ios-orientationchange-fix.js',
					'bower_components/jquery.fitvids/jquery.fitvids.js',
					'bower_components/js-cookie/src/js.cookie.js',
					'bower_components/magnific-popup/dist/jquery.magnific-popup.js',
					'bower_components/slick-carousel/slick/slick.js',
					'js/_affix.js',
					'js/_analytics-youtube.js',
					'js/_animations.js',
					'js/_archives.js',
					'js/_bookmarks.js',
					'js/_calendar.js',
					'js/_captcha.js',
					'js/_components.js',
					'js/_directory.js',
					'js/_facebook.js',
					'js/_header-sticky.js',
					'js/_home.js',
					'js/_infinite-scroll.js',
					'js/_navigation.js',
					'js/_ratings.js',
					'js/_slider.js',
					'js/_subscriptions.js',
					'js/_users.js',
					'js/_widgets.js',
					'js/scripts.js'
				],
				dest: 'build/js/scripts.js',
				nonull: true
			},
			calendar: {
				src: [
					'bower_components/moment/moment.js',
					'bower_components/pikaday/pikaday.js',
					'bower_components/pikaday/plugins/pikaday.jquery.js',
					'js/calendar.js',
				],
				dest: 'build/js/calendar.js',
				nonull: true
			},
			forms: {
				src: [
					'bower_components/moment/moment.js',
					'bower_components/pikaday/pikaday.js',
					'bower_components/pikaday/plugins/pikaday.jquery.js',
					'js/forms.js',
				],
				dest: 'build/js/forms.js',
				nonull: true
			},
			admin: {
				src: [
					'js/admin.js'
				],
				dest: 'build/js/admin.js',
				nonull: true
			},
			admin: {
				src: [
					'js/tinymce.js'
				],
				dest: 'build/js/tinymce.js',
				nonull: true
			}
		},

		uglify: {
			options: {
				preserveComments: 'some'
			},
			build: {
				files: {
					'build/js/scripts.min.js': 'build/js/scripts.js',
					'build/js/calendar.min.js': 'build/js/calendar.js',
					'build/js/forms.min.js': 'build/js/forms.js',
					'build/js/admin.min.js': 'build/js/admin.js',
					'build/js/tinymce.min.js': 'build/js/tinymce.js'
				}
			}
		},

		autoprefixer: {
			options: {
				cascade: true
			},
			build: {
				files: {
					'build/css/style.css': ['build/css/style.css'],
					'build/css/profiles.css': ['build/css/profiles.css'],
					'build/css/admin.css': ['build/css/admin.css']
				}
			}
		},

		csso: {
			options: {
				report: 'min'
			},
			build: {
				files: {
					'build/css/style.min.css': ['build/css/style.css'],
					'build/css/profiles.min.css': ['build/css/profiles.css'],
					'build/css/admin.min.css': ['build/css/admin.css']
				}
			}
		},

		imagemin: {
			options: {
				cache: false // Bug: https://github.com/gruntjs/grunt-contrib-imagemin/issues/140
			},
			build: {
				files: [{
					expand: true,
					cwd: 'images/',
					src: ['**/*.{png,jpg,gif,svg}'],
					dest: 'build/images/'
				}]
			}
		},

		watch: {
			js: {
				files: ['js/**/*.js'],
				tasks: ['concat'],
				options: {
					spawn: false
				}
			},

			css: {
				files: ['sass/**/*.scss'],
				tasks: ['sass', 'autoprefixer'],
				options: {
					spawn: false
				}
			},

			images: {
				files: ['images/**/*'],
				tasks: ['newer:imagemin'],
				options: {
					spawn: false
				}
			}
		}

	});

	grunt.loadNpmTasks('grunt-autoprefixer');
	grunt.loadNpmTasks('grunt-contrib-clean');
	grunt.loadNpmTasks('grunt-contrib-concat');
	grunt.loadNpmTasks('grunt-contrib-csslint');
	grunt.loadNpmTasks('grunt-contrib-imagemin');
	grunt.loadNpmTasks('grunt-contrib-jshint');
	grunt.loadNpmTasks('grunt-contrib-sass');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-csscomb');
	grunt.loadNpmTasks('grunt-csso');
	grunt.loadNpmTasks('grunt-newer');
	grunt.loadNpmTasks('grunt-notify');

	grunt.registerTask('default', ['clean', 'sass', 'concat', 'imagemin', 'autoprefixer', 'watch']);
	grunt.registerTask('build', ['clean', 'csscomb', 'sass', 'jshint', 'concat', 'uglify', 'imagemin', 'autoprefixer', 'csso']);

};
