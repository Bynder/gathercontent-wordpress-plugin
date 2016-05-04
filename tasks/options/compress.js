module.exports = {
	main: {
		options: {
			mode: 'zip',
			archive: './release/gathercontent.<%= pkg.version %>.zip'
		},
		expand: true,
		cwd: 'release/<%= pkg.version %>/',
		src: ['**/*'],
		dest: 'gathercontent/'
	}
};