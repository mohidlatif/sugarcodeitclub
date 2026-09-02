var menu = document.querySelector('.menu');
var navLinks = document.querySelector('.links');

if (menu && navLinks) {
  menu.addEventListener('click', function () {
    var isOpen = navLinks.classList.toggle('open');
    menu.setAttribute('aria-expanded', String(isOpen));
  });
}

var currentPage =
  window.location.pathname.split('/').pop() ||
  'index.html';

document
  .querySelectorAll('.links a')
  .forEach(function (link) {

    var linkPage =
      link.getAttribute('href').split('/').pop();

    if (linkPage === currentPage) {
      link.classList.add('active');
    }
  });


function escapeText(value) {
  var div =
    document.createElement('div');

  div.textContent =
    value || '';

  return div.innerHTML;
}


function setupCalendar() {

  var calendar =
    document.getElementById(
      'monthCalendar'
    );

  if (!calendar) {
    return;
  }

  var title =
    document.getElementById(
      'calendarTitle'
    );

  var prev =
    document.getElementById(
      'prevMonth'
    );

  var next =
    document.getElementById(
      'nextMonth'
    );

  var events = [];

  var month = 7;
  var year = 2026;

  var firstMonth =
    new Date(
      2026,
      7,
      1
    );

  var lastMonth =
    new Date(
      2027,
      4,
      1
    );

  var monthNames = [
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December'
  ];

  var dayNames = [
    'Sun',
    'Mon',
    'Tue',
    'Wed',
    'Thu',
    'Fri',
    'Sat'
  ];


  function dateKey(y, m, d) {

    return (
      y +
      '-' +
      String(m + 1).padStart(2, '0') +
      '-' +
      String(d).padStart(2, '0')
    );
  }


  function render() {

    calendar.innerHTML = '';

    if (title) {
      title.textContent =
        monthNames[month] +
        ' ' +
        year;
    }


    dayNames.forEach(
      function (name) {

        var heading =
          document.createElement(
            'div'
          );

        heading.className =
          'calendar-day-name';

        heading.textContent =
          name;

        calendar.appendChild(
          heading
        );
      }
    );


    var firstDay =
      new Date(
        year,
        month,
        1
      ).getDay();


    var daysInMonth =
      new Date(
        year,
        month + 1,
        0
      ).getDate();


    for (
      var blank = 0;
      blank < firstDay;
      blank += 1
    ) {

      var spacer =
        document.createElement(
          'div'
        );

      spacer.className =
        'calendar-cell calendar-blank';

      calendar.appendChild(
        spacer
      );
    }


    for (
      var day = 1;
      day <= daysInMonth;
      day += 1
    ) {

      var key =
        dateKey(
          year,
          month,
          day
        );


      var cell =
        document.createElement(
          'div'
        );

      cell.className =
        'calendar-cell public-calendar-cell';


      var dateNumber =
        document.createElement(
          'span'
        );

      dateNumber.className =
        'date-number';

      dateNumber.textContent =
        day;

      cell.appendChild(
        dateNumber
      );


      var dayEvents =
        events.filter(
          function (item) {

            return (
              String(
                item.date || ''
              ).trim() === key
            );
          }
        );


      dayEvents.forEach(
        function (item) {

          var eventLine =
            document.createElement(
              'span'
            );

          eventLine.className =
            'calendar-event public-calendar-event';


          var eventName =
            item.name ||
            'Club event';


          var eventTime =
            item.time ||
            '';


          var eventText =
            eventTime
              ? eventTime +
                ' ' +
                eventName
              : eventName;


          if (item.cancelled) {

            eventText =
              'CANCELLED — ' +
              eventText;

            eventLine.classList.add(
              'cancelled-event'
            );
          }


          eventLine.textContent =
            eventText;


          if (item.details) {

            eventLine.title =
              item.details;
          }


          cell.appendChild(
            eventLine
          );
        }
      );


      calendar.appendChild(
        cell
      );
    }


    if (prev) {

      prev.disabled =
        new Date(
          year,
          month,
          1
        ) <= firstMonth;
    }


    if (next) {

      next.disabled =
        new Date(
          year,
          month,
          1
        ) >= lastMonth;
    }
  }


  if (prev) {

    prev.addEventListener(
      'click',
      function () {

        var previousMonth =
          new Date(
            year,
            month - 1,
            1
          );

        if (
          previousMonth <
          firstMonth
        ) {
          return;
        }

        month =
          previousMonth.getMonth();

        year =
          previousMonth.getFullYear();

        render();
      }
    );
  }


  if (next) {

    next.addEventListener(
      'click',
      function () {

        var followingMonth =
          new Date(
            year,
            month + 1,
            1
          );

        if (
          followingMonth >
          lastMonth
        ) {
          return;
        }

        month =
          followingMonth.getMonth();

        year =
          followingMonth.getFullYear();

        render();
      }
    );
  }


  fetch(
    '/api/events.php?nocache=' +
    Date.now(),
    {
      cache: 'no-store'
    }
  )

  .then(
    function (response) {

      if (!response.ok) {

        throw new Error(
          'Could not load events'
        );
      }

      return response.json();
    }
  )

  .then(
    function (data) {

      events =
        Array.isArray(data)
          ? data
          : [];

      render();
    }
  )

  .catch(
    function (error) {

      console.error(
        'Calendar error:',
        error
      );

      events = [];

      render();
    }
  );


  render();
}


function setupPhotoGalleries() {

  document
    .querySelectorAll(
      '[data-photo-section]'
    )
    .forEach(
      function (gallery) {

        var section =
          gallery.getAttribute(
            'data-photo-section'
          );


        fetch(
          '/api/photos.php?section=' +
          encodeURIComponent(
            section
          ) +
          '&nocache=' +
          Date.now(),
          {
            cache: 'no-store'
          }
        )

        .then(
          function (response) {

            if (!response.ok) {

              throw new Error(
                'Could not load photos'
              );
            }

            return response.json();
          }
        )

        .then(
          function (photos) {

            if (
              !Array.isArray(photos) ||
              photos.length === 0
            ) {

              gallery.innerHTML =
                '<div class="empty-state public-photo-empty">' +
                '<h3>More photos coming soon</h3>' +
                '<p>Club photos will be added here as the year continues.</p>' +
                '</div>';

              return;
            }


            gallery.innerHTML =
              '';


            photos
              .slice()
              .reverse()
              .forEach(
                function (photo) {

                  var figure =
                    document.createElement(
                      'figure'
                    );

                  figure.className =
                    'public-photo-card';


                  figure.innerHTML =
                    '<img src="' +
                    escapeText(
                      photo.file
                    ) +
                    '" alt="' +
                    escapeText(
                      photo.caption ||
                      'Sugar Code It photo'
                    ) +
                    '">' +
                    (
                      photo.caption
                        ? '<figcaption>' +
                          escapeText(
                            photo.caption
                          ) +
                          '</figcaption>'
                        : ''
                    );


                  gallery.appendChild(
                    figure
                  );
                }
              );
          }
        )

        .catch(
          function (error) {

            console.error(
              'Photo error:',
              error
            );

            gallery.innerHTML =
              '';
          }
        );
      }
    );
}


setupCalendar();
setupPhotoGalleries();