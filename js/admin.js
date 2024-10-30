function hex(x) {
    var hexDigits = new Array("0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "a", "b", "c", "d", "e", "f");
    return isNaN(x) ? "00" : hexDigits[(x - x % 16) / 16] + hexDigits[x % 16];
}
function rgb2hex(color) {
    var rgb = color.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
    if (rgb) {
        return "#" + hex(rgb[1]) + hex(rgb[2]) + hex(rgb[3]);
    } else {
        return color;
    }
}
jQuery(document).ready(function ($) {
    $('.qtip-table').on('change', 'input', {}, function (event) {
        qtip_handler();
    });
    
    function qtip_handler() {
        var b = $("input[name='ibs_calendar_options[qtip][style]']:checked").val();
        var c = $('#qtip-rounded').is(':checked') ? 'qtip-rounded' : '';
        var d = $('#qtip-shadow').is(':checked') ? 'qtip-shadow' : '';
        var styles = $.trim(b + ' ' + c + ' ' + d.replace('  ', ' '));
        $('#test-qtip').val(styles);
        $('#test-qtip').qtip({
            content: {'text': '<p> Current qtip classes test</p><p>' + styles + '</p>'},
            position: {
                my: 'bottom center',
                at: 'top center'
            },
            show: {ready: true
            },
            style: {
                classes: styles
            }
        });
    }
    qtip_handler();
    $(document).find('body')
            .append($('<div id="gcal-dropdown-start" class="gcal-dropdown ">')
                    .append($('<div class="gcal-dropdown-panel">').css({'width': '400px'})
                            .append($('<div>').html(
                                    '<div><label style="width:150px; display:inline-block"><b>now</b></label><span style="width:auto;"> start today</span></div>'
                                    + '<div><label style="width:150px; display:inline-block"><b>2011-11-01</b></label><span style="width:auto;">start with specified date </span> </div>'
                                    + '<div><label style="width:150px; display:inline-block"><b>positive number</b></label><span style="width:auto;"> start n days after today</span> </div>'
                                    + '<div><label style="width:150px; display:inline-block"><b>negative number</b></label><span style="width:auto;"> start n days prior to today</span></div>')
                                    ))
                    .hide())
            .append($('<div id="gcal-dropdown-timeFormat" class="gcal-dropdown ">')
                    .append($('<div class="gcal-dropdown-panel">').css({'width': '300px'})
                            .append($('<div>').html(
                                    '<div><label style="width:75px; display:inline-block"><b>24 Hour</b></label><span style="width:auto;"> H = 00...23,  HH = 00..23</span></div>'
                                    + '<div><label style="width:75px; display:inline-block"><b>12 Hour</b></label><span style="width:auto;">h = 1...12, hh = 01...12</span> </div>'
                                    + '<div><label style="width:75px; display:inline-block"><b>Minute</b></label><span style="width:auto;"> m = 1..59, mm = 01..59</span> </div>'
                                    + '<div><label style="width:75px; display:inline-block"><b>Second</b></label><span style="width:auto;"> s = 1..59, ss 01..59</span></div>'
                                    + '<div><label style="width:75px; display:inline-block"><b>Am/Pm</b></label><span style="width:auto;"> A = AM PM, a =  am pm</span></div>')
                                    ))
                    .hide())
            .append($('<div id="gcal-dropdown-dateFormat" class="gcal-dropdown ">')
                    .append($('<div class="gcal-dropdown-panel">').css({'width': '400px'})
                            .append($('<div>').html(
                                    '<div><label style="width:75px; display:inline-block" ><b>Month</b></label><span style="width:auto;">M MM : 1..12;&nbsp; MMM : Jan;&nbsp; MMMM :January</span></div>'
                                    + '<div><label style="width:75px; display:inline-block"><b>Day</b></label><span style="width:auto;">D DD : 1 01;&nbsp; Do : 1st;&nbsp; DDD DDDD : 1...365</span> </div>'
                                    + '<div><label style="width:75px; display:inline-block"><b>Year</b></label><span style="width:auto;">YY : 15;&nbsp; YYYY : 2015</span> </div>')
                                    ))
                    .hide());
    function closeHelp() {
        $('.gcal-dropdown').hide();
        $('a').each(function (index, item) {
            if ($(item).text() === 'Close') {
                $(item).text('Help');
            }
        });
    }
    $('.gcal-help').on('click', '', {}, function (event) {
        var rel = $(this).attr('rel');
        event.preventDefault();
        var open = null;
        var close = null;
        switch (rel) {
            case 'start':
                open = $('#gcal-dropdown-start');
                break;
            case 'time-help':
                open = $('#gcal-dropdown-timeFormat');
                break;
            case 'date-help' :
                open = $('#gcal-dropdown-dateFormat')
                break;
        }
        if (open) {
            if (open.is(':visible')) {
                open.hide();
                $(this).text('help');
            } else {
                closeHelp();
                $(this).text('close');
                open.css({position: 'absolute', left: $(this).offset().left, top: $(this).offset().top + $(this).height()});
                open.show();
            }
        }
        if (close) {
            close.trigger('click')
        }

    })
    
    $('.feed-color-box').click(function () {
        $('.feed-color-box').removeClass('feed-color-box-selected');
        $(this).addClass('feed-color-box-selected');
        var fid = $(this).attr('rel');
        var rgb = $(this).css('background-color');
        $('#ibs-feed-name-' + fid).css({'background-color': rgb, color: $('#colorpicker-fg-' + fid).val()});
        $('#colorpicker-bg-' + fid).val(rgb2hex(rgb));

    });
});