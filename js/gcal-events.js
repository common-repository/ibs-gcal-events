
function IBS_GCAL_EVENTS(args, mode) {

    this.run(args, mode);
}

(function ($) {
    function IsEmail(email) {
        var regex = /^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/;
        return regex.test(email);
    }
    $('.no-click').on('click', '', {}, function (event) {
        event.preventDefault();
    });

    function EventReader(source, callback) {
        var eventreader = this;
        this.events = [];
        var feedUrl = 'https://www.googleapis.com/calendar/v3/calendars/' +
                encodeURIComponent(source.url.trim()) + '/events?key=' + source.key +
                '&orderBy=startTime&singleEvents=true';
        var test = source.start.split('-');
        if (test.length !== 3) {
            if (source.start === 'now') {
                source.start = moment().format('YYYY-MM-DD');
            } else {
                var factor = parseInt(source.start);
                if (factor !== 'NaN') {
                    if (factor < 1) {
                        source.start = moment().subtract(Math.abs(factor), 'days').format('YYYY-MM-DD');
                    } else {
                        source.start = moment().add(factor, 'days').format('YYYY-MM-DD');
                    }
                }
            }
        }
        feedUrl += '&timeMin=' + new Date(source.start).toISOString();
        $.getJSON(feedUrl)
                .then(
                        function (data) {
                            $.each(data.items, function (e, item) {
                                if (typeof item.start.dateTime !== 'undefined') {
                                    var start = moment(item.start.dateTime);
                                } else {
                                    start = moment(item.start.date);
                                }
                                if (typeof item.end.dateTime !== 'undefined') {
                                    var end = moment(item.end.dateTime);
                                } else {
                                    start = moment(item.end.date);
                                }
                                var event = {
                                    source: source,
                                    id: e,
                                    title: item.summary || '',
                                    start: start,
                                    end: end,
                                    location: typeof item.location === 'undefined' ? '' : item.location,
                                    description: typeof item.description === 'undefined' ? '' : item.description,
                                    url: typeof item.htmlLink === 'undefined' ? '' : item.htmlLink
                                };
                                eventreader.events.push(event);
                            });
                            callback();
                        });

    }
    IBS_GCAL_EVENTS.prototype.run = function (args, mode) {
        if (typeof args.feeds_set !== 'undefined') {
            var keep = args.feeds_set.split(',');
            for (var i = 1; args.feedCount >= i; i++) {
                var index = i.toString();
                var fname = 'feed_' + index;
                if (keep.indexOf(index) == -1 && keep.length) {
                    args.feeds[fname].enabled = false;
                }
            }
        }
        if (typeof args['max'] !== 'undefined') {
            args.max = parseInt(args.max);
        }
        if (typeof args['height'] !== 'undefined' && args['height'] !== 'null' && args['height'] !== 'auto') {
            if (args.height) {
                args.height = parseInt(args.height);
            }
        }
        function fixbool(args) {
            var key;
            for (key in args) {
                if (typeof args[key] === 'object') {
                    return fixbool(args[key]);
                }
                if (typeof args[key] === 'string') {
                    switch (args[key].toLowerCase()) {
                        case "null" :
                            args[key] = null;
                            break;
                        case "true" :
                        case "yes" :
                            args[key] = true;
                            break;
                        case "false" :
                        case "no" :
                            args[key] = false;
                            break;
                        default :
                    }
                }
            }
        }
        function setTable() {
            var event_table = '#ibs-gcal-events-' + args.id;
            $(event_table).empty()
                    .append($('<table>').addClass('gcal-event-table').css({layout: 'fixed', width: '100%', border: '0'})
                            .append($('<theader>')
                                    .append($('<tr class="gcal-tr-th">').css({'text-align': 'center', })
                                            .append($('<th class="th1 gcal-th">').text('Day'))
                                            .append($('<th class="th2 gcal-th">').text('Time'))
                                            .append($('<th class="th3 gcal-th">').text('Event'))
                                            .append($('<th class="th4 gcal-th">').text('Location'))
                                            )
                                    )
                            .append($('<tbody>').attr({id: 'ibs-gcal-events-rows-' + args.id}).css({display: 'inline-block', height: args.height, overflow: 'auto'})

                                    )
                            )
                    .append($('<div id="gcal-legend-list-' + args.id + '" class="gcal-legend"></div>'));
            if (args.legend) {
                $('#gcal-legend-list-' + args.id).css({visibility: 'visible'})
            } else {
                $('#gcal-legend-list-' + args.id).css({visibility: 'hidden'})
            }
        }
        setTable();
        var readers = [];
        for (var feed in args.feeds) {
            if (args.feeds[feed].url !== '' && args.feeds[feed].enabled && IsEmail(args.feeds[feed]['url'])) {
                if (typeof args.feeds[feed]['key'] === 'string' && args.feeds[feed]['key'] !== '') {
                    var key = args.feeds[feed]['key'];
                } else {
                    key = 'AIzaSyBwcmfwl7W1aMyo9wnXwmASRfZ0sOhGhRc';
                }
                var event_source = {
                    'key': key,
                    'start': args.start,
                    'max': args.max,
                    'nolink': args.feeds[feed]['nolink'],
                    'nodesc': args.feeds[feed]['nodesc'],
                    'altlink': args.feeds[feed]['altlink'],
                    'feedName': args.feeds[feed]['name'],
                    'textColor': args.feeds[feed]['textColor'],
                    'backgroundColor': args.feeds[feed]['backgroundColor'],
                    'url': args.feeds[feed]['url'],
                };
                readers.push(new EventReader(event_source, display));
                $('#gcal-legend-list-' + args.id)
                        .append($('<span>').addClass('gcal-legend-color').css({'background-color': args.feeds[feed]['backgroundColor']}))
                        .append($('<span>').addClass('gcal-legend-name').text(args.feeds[feed]['name']))
            }
        }
        function display() {
            var events = [];
            for (var i in readers) {
                events = events.concat(readers[i].events);
            }
            events.sort(function (a, b) {
                if (a.start.isSame(b.start))
                    return 0;
                if (a.start.isAfter(b.start))
                    return 1;
                if (a.start.isBefore(b.start))
                    return -1;
            });
            if (args.descending) {
                events = events.reverse();
            }
            events = events.slice(0, parseInt(args.max));
            $('#ibs-gcal-events-rows-' + args.id).empty();
            for (var i = 0; i < events.length; i++) {
                var event = events[i];
                var source = events[i].source;
                var pattern = 'ddd MMM Do';
                var past = moment() > moment(events[i].start) ? '*' : '';
                var d = moment(events[i].start).format(pattern);
                var t = moment(events[i].start).format(args.timeFormat);
                if (typeof events[i].location === 'undefined' || events[i].location === null || events[i].location === '') {
                    l = '<span style="visibility:hidden">undefined</span>';
                } else {
                    l = '<span>' + events[i].location + '</span>';
                }
                var title = null;
                if (source.nolink) {
                    title = $('<a href="#" class="no-click">').html(past + event.title);
                } else {
                    if (source.altlink !== '') {
                        title = $('<a>').attr({href: source.altlink + event.id, target: '_blank'}).html(past + event.title);
                    } else {
                        title = $('<a>').attr({href: event.url, target: '_blank'}).html(past + event.title);
                    }
                }
                $(title).css({width: '200px', 'text-decoration': 'none', padding: '3px', color: source.textColor, 'background-color': source.backgroundColor});
                $('#ibs-gcal-events-rows-' + args.id)
                        .append($('<tr class="gcal-tr-td">').css({height: '25px', color: source.textColor, 'background-color': source.backgroundColor, 'text-decoration': 'none'})
                                .prop('disabled', past)
                                .append($('<td class="td1 gcal-td">').text(d))
                                .append($('<td class="td2 gcal-td">').text(t))
                                .append($('<td class="td3 gcal-td">').append($(title).css({cursor: 'pointer'}).qtip(qtip_params(event))))
                                .append($('<td class="td4 gcal-td">').html(l))
                                );
            }
        }
        function qtip_params(event) {
            var fmt = args.dateFormat + ' ' + args.timeFormat;
            var title = args.qtip.title.replace('%title%', event.title);
            var location = '';
            if (typeof event.location !== 'undefined' && event.location !== '') {
                location = args.qtip.location.replace('%location%', event.location);// <p>%location%</p>
            }
            var description = '';
            if (!args.nodesc && typeof event.description !== 'undefined' && event.description !== '') {
                description = args.qtip.description.replace('%description%', event.description); // '<p>%description%</p>'
            }
            var time = '';
            time = moment(event.start).format(fmt) + moment(event.end).format(' - ' + args.timeFormat);
            if (event.allDay) {
                time = moment(event.start).format(args.dateFormat) + '  All day';
            }
            time = args.qtip.time.replace('%time%', time);// <p>time<p>
            var order = args.qtip.order.replace('%title%', title).replace('%location%', location).replace('%description%', description).replace('%time%', time);
            return {
                content: {'text': order},
                position: {
                    my: 'bottom left',
                    at: 'top right'
                },
                style: {
                    classes: args['qtip']['style'] + ' ' + args['qtip']['rounded'] + args['qtip']['shadow']

                },
                show: {
                    event: 'mouseover'
                },
                hide: {
                    fixed: true,
                    delay: 250,
                    event: 'mouseout mouseleave'

                }
            };
        }
    }

}(jQuery));