$(document).ready(function() {

    var jsonDateFormat = "YYYY-MM-DD HH:mm:ss";

    if (fullCalendarCanWrite == 'false' || fullCalendarCanWrite == false) {
        fullCalendarCanWrite = false;
    } else {
        fullCalendarCanWrite = true;
    }

    var calendar = $('#calendar').fullCalendar({
        timezone: fullCalendarTimezone,
        lang: fullCalendarLanguage,
//        defaultView: 'agendaWeek',
        defaultView: 'month',
        aspectRatio: 1.5,
        header: {
            left: 'prev,next today',
            center: 'title',
            right: 'month,agendaWeek,agendaDay'
        },
        editable: fullCalendarCanWrite,
        events: {
            url: fullCalendarLoadUrl.replace('-selectors-', fullCalendarSelectors),
            data: {selectors: fullCalendarSelectors, filters: fullCalendarFilters},
            error: function() {
                //alert("loading error!");
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
                //remote: editUrl
            });
            $('#globalModal').load(editUrl);
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




    $(".selectorCheckbox").click(function() {
        reloadFullCalendar();
    });
    $(".filterCheckbox").click(function() {

        // Make sure responded / not resondend  filters are not checked
        // at the same time
        if ($(this).val() == '3') {
            $(":checkbox[value=4][name='filter']").attr("checked", false);
        }
        if ($(this).val() == '4') {
            $(":checkbox[value=3][name='filter']").attr("checked", false);
        }
        
        reloadFullCalendar();
    });

});



function reloadFullCalendar() {
    fullCalendarSelector = "";
    fullCalendarFilter = "";

    $(".selectorCheckbox").each(function() {
        if ($(this).prop( "checked" )) {
            fullCalendarSelector += $(this).val() + ",";
        }
    });
    $(".filterCheckbox").each(function() {
        if ($(this).prop( "checked" )) {
            fullCalendarFilter += $(this).val() + ",";
        }
    });

    var events = {
        url: fullCalendarLoadUrl,
        type: 'GET',
        data: {
            selectors: fullCalendarSelector,
            filters: fullCalendarFilter,
        }
    };

    $('#calendar').fullCalendar('removeEventSource', events);
    $('#calendar').fullCalendar('addEventSource', events);
    $('#calendar').fullCalendar('refetchEvents');

}