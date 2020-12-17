/* jshint node:true */
/* jshint es3:false */
/* jshint esversion:6 */
/* jshint quotmark:false */

const buildTools = require( './tools/build' );

const SOURCE_DIR = './';
const BUILD_DIR = './build/';

module.exports = function(grunt) {
    buildTools.setGruntReference( grunt );

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
					'!**/*.min.js',
					'!js/twemoji.js',
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
				]
			}
		},
		concat: {
			emoji: {
				options: {
					separator: '\n',
					process(src, filepath) {
						return `// Source: ${filepath.replace( BUILD_DIR, '' )}\n${src}`;
					}
				},
				src: [
					`${BUILD_DIR}js/twemoji.min.js`,
					`${BUILD_DIR}js/wp-emoji.min.js`
				],
				dest: `${BUILD_DIR}js/wp-emoji-release.min.js`
			}
		},
		includes: {
			emoji: {
				src: `${BUILD_DIR}/emoji.php`,
				dest: '.'
			}
		},
		replace: {
			emojiRegex: {
				options: {
					patterns: [
						{
							match: /\/\/ START: emoji arrays[\S\s]*\/\/ END: emoji arrays/g,
							replacement: buildTools.replaceEmojiRegex,
						}
					]
				},
				files: [
					{
						expand: true,
						flatten: true,
						src: [
							`${SOURCE_DIR}/emoji.php`
						],
						dest: `${SOURCE_DIR}`
					}
				]
			}
		}
	});

	grunt.registerTask( 'precommit:emoji', [
		'replace:emojiRegex'
	] );

	grunt.registerTask( 'build', [
		'clean:all',
		'copy',
		'terser:core',
		'concat:emoji',
		'includes:emoji'
	] );
};
