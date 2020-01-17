module.exports = function (grunt) {

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        clean: ["assets/*"],
        uglify: {
            calendar: {
                files: {
                    'resources/js/humhub.calendar.Calendar.min.js': ['resources/js/humhub.calendar.Calendar.js'],
                    'resources/js/humhub.calendar.min.js': ['resources/js/humhub.calendar.js'],
                    'resources/js/humhub.calendar.recurrence.Form.min.js': ['resources/js/humhub.calendar.recurrence.Form.js'],
                    'resources/js/humhub.calendar.reminder.Form.min.js': ['resources/js/humhub.calendar.reminder.Form.js'],
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
                    'node_modules/@fullcalendar/core/main.min.css',
                    'node_modules/@fullcalendar/daygrid/main.min.css',
                    'node_modules/@fullcalendar/timegrid/main.min.css',
                    'node_modules/@fullcalendar/list/main.min.css',
                    'resources/js/theme/bootstrap/main.min.css',
                ],
                dest: 'resources/css/fullcalendar.bundle.min.css'
            }
        }
    });

    //grunt.loadNpmTasks('grunt-contrib-less');
    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-uglify');
   // grunt.loadNpmTasks('grunt-contrib-cssmin');


    grunt.registerTask('build', ['concat', 'uglify']);
};
