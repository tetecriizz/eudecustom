module.exports = function(grunt) {

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        uglify: {
            build: {
                files: [{
                    src: 'amd/src/config.js',
                    dest: 'amd/build/config.min.js',
                },
                {
                    src: 'amd/src/datatables.js',
                    dest: 'amd/build/datatables.min.js',
                },
                {
                    src: 'amd/src/datatables_buttons.js',
                    dest: 'amd/build/datatables_buttons.min.js',
                },
                {
                    src: 'amd/src/eude.js',
                    dest: 'amd/build/eude.min.js',
                },]
            }
        }
    });
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.registerTask('default', ['uglify']);
};