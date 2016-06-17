module.exports = {
	all: {
		files: {
			'assets/js/gathercontent.min.js': ['assets/js/gathercontent.js'],
			'assets/js/gathercontent-mapping.min.js': ['assets/js/gathercontent-mapping.js'],
			'assets/js/gathercontent-sync.min.js': ['assets/js/gathercontent-sync.js']
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
