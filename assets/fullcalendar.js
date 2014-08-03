$(document).ready(function() {

    var jsonDateFormat = "YYYY-MM-DD HH:mm:ss";

    var calendar = $('#calendar').fullCalendar({
        timezone: fullCalendarTimezone,
        lang: fullCalendarLanguage,
        defaultView: 'agendaWeek',
        header: {
            left: 'prev,next today',
            center: 'title',
            right: 'month,agendaWeek,agendaDay'
        },
        editable: fullCalendarCanWrite,
        events: {
            url: fullCalendarLoadUrl,
            error: function() {
                alert("loading error!");
            }
        },
        selectable: fullCalendarCanWrite,
        selectHelper: fullCalendarCanWrite,
        select: function(start, end) {
            var editUrl = fullCalendarCreateUrl;
            editUrl = editUrl.replace('-start-', encodeURIComponent(start.format(jsonDateFormat)));
            editUrl = editUrl.replace('-end-', encodeURIComponent(end.format(jsonDateFormat)));
            $('#globalModal').modal({
                show: 'true',
                remote: editUrl
            });
            calendar.fullCalendar('unselect');
        },
        eventResize: function(event, delta, revertFunc) {
            editUrl = event.updateUrl.replace('-end-', encodeURIComponent(event.end.format(jsonDateFormat)));
            editUrl = editUrl.replace('-start-', '');
            editUrl = editUrl.replace('-id-', encodeURIComponent(event.id));
            $.ajax({
                url: editUrl
            });
        },
        eventDrop: function(event, delta, revertFunc) {
            editUrl = event.updateUrl.replace('-start-', encodeURIComponent(event.start.format(jsonDateFormat)));
            editUrl = editUrl.replace('-end-', encodeURIComponent(event.end.format(jsonDateFormat)));
            editUrl = editUrl.replace('-id-', encodeURIComponent(event.id));
            $.ajax({
                url: editUrl
            });
        },
        eventClick: function(event, element) {
            window.location = event.viewUrl;
        },
        loading: function(bool) {
            $('#loading').toggle(bool);
        }
    });
});