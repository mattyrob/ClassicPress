/* jshint node:true */
/* jshint es3:false */
/* jshint esversion:6 */
/* jshint quotmark:false */

const SOURCE_DIR = './';
const BUILD_DIR = './build/';

module.exports = function(grunt) {
    // Load tasks.
	for ( const devDep in require( './package.json' ).devDependencies ) {
		// Match: grunt-abc, @author/grunt-xyz
		// Skip: grunt-legacy-util
		if ( /^(@[^\/]+\/)?grunt-(?!legacy-util$)/.test( devDep ) ) {
			grunt.loadNpmTasks( devDep );
		}
	}

    // Load legacy utils.
    grunt.util = require( 'grunt-legacy-util' );

    // Project configuration.
    grunt.initConfig({
		clean: {
			all: [BUILD_DIR]
		},
		copy: {
			files: {
				files: [
					{
						dot: true,
						expand: true,
						cwd: SOURCE_DIR,
						src: [
							'**',
							'!.*',
							'!package.json',
							'!package-lock.json',
							'!Gruntfile.js',
							'!node_modules/**'
						],
						dest: BUILD_DIR
					}
				]
			}
		},
		cssmin: {
			options: {
				compatibility: 'ie7'
			},
			core: {
				expand: true,
				cwd: SOURCE_DIR,
				dest: BUILD_DIR,
				ext: '.min.css',
				src: [
					'css/*.css'
				]
			}
		},
		jshint: {
			options: grunt.file.readJSON('.jshintrc'),
			grunt: {
				src: ['Gruntfile.js']
			},
			core: {
				expand: true,
				cwd: SOURCE_DIR,
				src: [
					'js/*.js',
					'!**/*.min.js'
				],
				// Remove once other JSHint errors are resolved
				options: {
					curly: false,
					eqeqeq: false
				},
				// Limit JSHint's run to a single specified file:
				//
				//    grunt jshint:core --file=filename.js
				//
				// Optionally, include the file path:
				//
				//    grunt jshint:core --file=path/to/filename.js
				//
				filter(filepath) {
                    let index;
                    const file = grunt.option( 'file' );

                    // Don't filter when no target file is specified
                    if ( ! file ) {
						return true;
					}

                    // Normalize filepath for Windows
                    filepath = filepath.replace( /\\/g, '/' );
                    index = filepath.lastIndexOf( `/${file}` );

                    // Match only the filename passed from cli
                    if ( filepath === file || ( -1 !== index && index === filepath.length - ( file.length + 1 ) ) ) {
						return true;
					}

                    return false;
                }
			}
		},
		terser: {
			// Settings for all subtasks
			options: {
				output: {
					ascii_only: true
				},
				ie8: true
			},
			// Subtasks
			core: {
				expand: true,
				cwd: SOURCE_DIR,
				dest: BUILD_DIR,
				ext: '.min.js',
				src: [
					'js/*.js',

					// Exceptions
					'!assets/wp-embed.js' // We have extra options for this, see terser:embed
				]
			},
			embed: {
				options: {
					compress: {
						conditionals: false
					}
				},
				expand: true,
				cwd: SOURCE_DIR,
				dest: BUILD_DIR,
				ext: '.min.js',
				src: ['js/wp-embed.js']
			}
		},
		includes: {
			embed: {
				src: `${BUILD_DIR}embed.php`,
				dest: '.'
			}
		}
	});

    // JSHint task.
    grunt.registerTask( 'jshint:corejs', [
		'jshint:grunt',
		'jshint:core'
	] );

    grunt.registerTask( 'build', [
		'clean',
		'copy',
		'cssmin',
		'terser:core',
		'terser:embed',
		'includes:embed'
	] );

    // Default task.
    grunt.registerTask('default', ['build']);
};
