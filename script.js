var menu =
  document.querySelector(
    '.menu'
  );


var navLinks =
  document.querySelector(
    '.links'
  );


if (
  menu &&
  navLinks
) {

  menu.addEventListener(
    'click',
    function () {

      var open =
        navLinks.classList.toggle(
          'open'
        );


      menu.setAttribute(
        'aria-expanded',
        String(open)
      );

    }
  );

}


/*
|--------------------------------------------------------------------------
| Active navigation
|--------------------------------------------------------------------------
*/

var currentPage =
  window.location.pathname
    .split('/')
    .pop() ||
  'index.html';


document
  .querySelectorAll(
    '.links a'
  )
  .forEach(
    function (link) {

      var href =
        link.getAttribute(
          'href'
        ) || '';


      var linkPage =
        href
          .split('/')
          .pop();


      if (
        linkPage ===
        currentPage
      ) {

        link.classList.add(
          'active'
        );
      }

    }
  );


/*
|--------------------------------------------------------------------------
| Calendar
|--------------------------------------------------------------------------
*/

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


  function dateKey(
    y,
    m,
    d
  ) {

    return (
      y +
      '-' +
      String(
        m + 1
      ).padStart(2,'0') +
      '-' +
      String(d)
        .padStart(2,'0')
    );
  }


  function render() {

    calendar.innerHTML =
      '';


    if (title) {

      title.textContent =
        monthNames[month] +
        ' ' +
        year;
    }


    dayNames.forEach(
      function (dayName) {

        var heading =
          document.createElement(
            'div'
          );


        heading.className =
          'calendar-day-name';


        heading.textContent =
          dayName;


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
      blank++
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
      day++
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


      var number =
        document.createElement(
          'span'
        );


      number.className =
        'date-number';


      number.textContent =
        day;


      cell.appendChild(
        number
      );


      events
        .filter(
          function (event) {

            return (
              String(
                event.date ||
                ''
              ).trim() ===
              key
            );
          }
        )

        .forEach(
          function (event) {

            var line =
              document.createElement(
                'span'
              );


            line.className =
              'calendar-event public-calendar-event';


            var name =
              event.name ||
              'Club event';


            var time =
              event.time ||
              '';


            var text =
              time
                ? time +
                  ' ' +
                  name
                : name;


            var cancelled =
              event.cancelled === true ||
              event.cancelled === 1 ||
              event.cancelled === '1' ||
              event.cancelled === 'true';


            if (cancelled) {

              text =
                'CANCELLED — ' +
                text;


              line.classList.add(
                'cancelled-event'
              );
            }


            line.textContent =
              text;


            if (
              event.details
            ) {

              line.title =
                event.details;
            }


            cell.appendChild(
              line
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
        ) <=
        firstMonth;
    }


    if (next) {

      next.disabled =
        new Date(
          year,
          month,
          1
        ) >=
        lastMonth;
    }

  }


  if (prev) {

    prev.addEventListener(
      'click',
      function () {

        var previous =
          new Date(
            year,
            month - 1,
            1
          );


        if (
          previous <
          firstMonth
        ) {
          return;
        }


        month =
          previous.getMonth();


        year =
          previous.getFullYear();


        render();

      }
    );
  }


  if (next) {

    next.addEventListener(
      'click',
      function () {

        var following =
          new Date(
            year,
            month + 1,
            1
          );


        if (
          following >
          lastMonth
        ) {
          return;
        }


        month =
          following.getMonth();


        year =
          following.getFullYear();


        render();

      }
    );
  }


  fetch(
    '/api/events.php?nocache=' +
    Date.now(),
    {
      cache:
        'no-store'
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


/*
|--------------------------------------------------------------------------
| Standard public photo galleries
|--------------------------------------------------------------------------
|
| Team is handled separately by about.html
| because it needs grouping by year.
|--------------------------------------------------------------------------
*/

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
            cache:
              'no-store'
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

            gallery.innerHTML =
              '';


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


            /*
             * No reverse().
             *
             * API/Admin position determines
             * the display sequence.
             */

            photos.forEach(
              function (photo) {

                var figure =
                  document.createElement(
                    'figure'
                  );


                figure.className =
                  'public-photo-card';


                var image =
                  document.createElement(
                    'img'
                  );


                image.src =
                  photo.file ||
                  '';


                image.alt =
                  photo.caption ||
                  'Sugar Code It photo';


                image.loading =
                  'lazy';


                figure.appendChild(
                  image
                );


                if (
                  photo.caption
                ) {

                  var caption =
                    document.createElement(
                      'figcaption'
                    );


                  caption.textContent =
                    photo.caption;


                  figure.appendChild(
                    caption
                  );
                }


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
              '<div class="empty-state public-photo-empty">' +
              '<h3>Photos could not be loaded</h3>' +
              '</div>';
          }
        );

      }
    );

}


setupCalendar();
setupPhotoGalleries();