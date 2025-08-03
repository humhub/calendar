module.exports = function (grunt) {

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        uglify: {
            calendar: {
                files: {
                    'resources/js/humhub.calendar.Calendar.min.js': ['resources/js/humhub.calendar.Calendar.js'],
                    'resources/js/humhub.calendar.min.js': ['resources/js/humhub.calendar.js'],
                    'resources/js/humhub.calendar.recurrence.Form.min.js': ['resources/js/humhub.calendar.recurrence.Form.js'],
                    'resources/js/humhub.calendar.reminder.Form.min.js': ['resources/js/humhub.calendar.reminder.Form.js'],
                    'resources/js/humhub.calendar.participation.Form.min.js': ['resources/js/humhub.calendar.participation.Form.js'],
                }
            }
        },
        cssmin: {
            target: {
                files: {
                    'resources/css/feathericons.min.css': ['resources/css/feathericons.css'],
                    'resources/css/calendar.min.css': ['resources/css/calendar.css']
                }
            }
        },
        concat: {
            fullcalendarJs: {
                src:[
                    'node_modules/moment/min/moment-with-locales.min.js',
                    'node_modules/moment-timezone/builds/moment-timezone-with-data.js',
                    'node_modules/@fullcalendar/core/main.min.js',
                    'node_modules/@fullcalendar/core/locales-all.min.js',
                    'node_modules/@fullcalendar/daygrid/main.min.js',
                    'node_modules/@fullcalendar/timegrid/main.min.js',
                    'node_modules/@fullcalendar/list/main.min.js',
                    'node_modules/@fullcalendar/interaction/main.min.js',
                    'node_modules/@fullcalendar/moment/main.min.js',
                    'node_modules/@fullcalendar/moment-timezone/main.min.js',
                    'resources/js/theme/bootstrap/main.min.js',
                ],
                dest: 'resources/js/fullcalendar.bundle.min.js'
            },
            fullcalendarCss: {
                src:[
                    'resources/css/feathericons.min.css',
                    'node_modules/@fullcalendar/core/main.min.css',
                    'node_modules/@fullcalendar/daygrid/main.min.css',
                    'node_modules/@fullcalendar/timegrid/main.min.css',
                    'node_modules/@fullcalendar/list/main.min.css',
                    'resources/js/theme/bootstrap/main.min.css',
                ],
                dest: 'resources/css/fullcalendar.bundle.min.css'
            }
        },
        watch: {
            scripts: {
                files: ['resources/js/*.js', 'resources/css/*.css'],
                tasks: ['build'],
                options: {
                    spawn: false,
                },
            },
        }
    });

    //grunt.loadNpmTasks('grunt-contrib-less');
    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-cssmin');
    grunt.loadNpmTasks('grunt-contrib-watch');

    grunt.registerTask('build', ['concat', 'uglify', 'cssmin']);
};
