module.exports = {
	all: {
		files: {
			'assets/js/gathercontent-importer.min.js': ['assets/js/gathercontent-importer.js']
		},
		options: {
			banner: '/*! <%= pkg.title %> - v<%= pkg.version %> - <%= grunt.template.today("yyyy-mm-dd") %>' +
			' | <%= pkg.homepage %>' +
			' | Copyright (c) <%= grunt.template.today("yyyy") %> <%= pkg.author.name %>' +
			' | Licensed <%= pkg.license %>' +
			' */\n',
			mangle: {
				except: ['jQuery']
			}
		}
	}
};
