module.exports = {
	options: {
		stripBanners: true,
		banner: '/**\n' + ' * <%= pkg.title %> - v<%= pkg.version %> - <%= grunt.template.today("yyyy-mm-dd") %>\n' + ' * <%= pkg.homepage %>\n' + ' *\n' + ' * Copyright (c) <%= grunt.template.today("yyyy") %> <%= pkg.author.name %>\n' + ' * Licensed under the <%= pkg.license %> license.\n' + ' */\n',
		transform: [
			'babelify',
			'browserify-shim'
		]
	},
	dist: { files: {
		'assets/js/gathercontent-importer.js' : 'assets/js/src/components/main.js'
	} }
};
