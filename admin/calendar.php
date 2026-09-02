<?php
session_start();

$root = dirname(__DIR__);
$eventsFile = $root . '/data/events.json';

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function readJsonFile($path) {
    if (!is_file($path)) {
        return [];
    }

    $json = file_get_contents($path);

    if ($json === false) {
        return [];
    }

    $data = json_decode($json, true);

    return is_array($data) ? $data : [];
}

function writeJsonFile($path, $data) {
    $json = json_encode(
        $data,
        JSON_PRETTY_PRINT |
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );

    if ($json === false) {
        return false;
    }

    return file_put_contents(
        $path,
        $json . PHP_EOL,
        LOCK_EX
    ) !== false;
}

function cleanText($value, $maxLength) {
    $value = trim((string) $value);

    return function_exists('mb_substr')
        ? mb_substr($value, 0, $maxLength)
        : substr($value, 0, $maxLength);
}

function makeId() {
    return bin2hex(random_bytes(8));
}

function h($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function validDateString($date) {
    if ($date === '') {
        return false;
    }

    $valid = DateTime::createFromFormat(
        'Y-m-d',
        $date
    );

    return (
        $valid &&
        $valid->format('Y-m-d') === $date
    );
}

function validTimeString($time) {
    if ($time === '') {
        return true;
    }

    return preg_match(
        '/^([01]\d|2[0-3]):[0-5]\d$/',
        $time
    ) === 1;
}

function formatEventTime($start, $end) {
    if ($start === '') {
        return '';
    }

    $startTimestamp = strtotime($start);

    if ($startTimestamp === false) {
        return '';
    }

    $startFormatted = date(
        'g:i A',
        $startTimestamp
    );

    if ($end === '') {
        return $startFormatted;
    }

    $endTimestamp = strtotime($end);

    if ($endTimestamp === false) {
        return $startFormatted;
    }

    $endFormatted = date(
        'g:i A',
        $endTimestamp
    );

    return
        $startFormatted .
        ' - ' .
        $endFormatted;
}

function eventAlreadyExists(
    $events,
    $date,
    $name,
    $ignoreId = ''
) {
    foreach ($events as $event) {

        $eventId =
            (string) ($event['id'] ?? '');

        if (
            $ignoreId !== '' &&
            $eventId === $ignoreId
        ) {
            continue;
        }

        $existingDate =
            (string) ($event['date'] ?? '');

        $existingName =
            strtolower(
                trim(
                    (string) (
                        $event['name'] ?? ''
                    )
                )
            );

        if (
            $existingDate === $date &&
            $existingName ===
            strtolower(trim($name))
        ) {
            return true;
        }
    }

    return false;
}

function sortEvents(&$events) {
    usort(
        $events,
        function ($a, $b) {

            $aValue =
                ($a['date'] ?? '') .
                ' ' .
                ($a['start_time'] ?? '');

            $bValue =
                ($b['date'] ?? '') .
                ' ' .
                ($b['start_time'] ?? '');

            return strcmp(
                $aValue,
                $bValue
            );
        }
    );
}


/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] =
        bin2hex(random_bytes(24));
}


/*
|--------------------------------------------------------------------------
| Initial variables
|--------------------------------------------------------------------------
*/

$message = '';
$error = '';

$events = readJsonFile(
    $eventsFile
);


/*
|--------------------------------------------------------------------------
| POST actions
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token =
        (string) (
            $_POST['csrf'] ?? ''
        );

    if (
        !hash_equals(
            $_SESSION['csrf'],
            $token
        )
    ) {
        http_response_code(403);
        exit(
            'Invalid request token.'
        );
    }

    $action =
        (string) (
            $_POST['action'] ?? ''
        );


    /*
    |--------------------------------------------------------------------------
    | Save event
    |--------------------------------------------------------------------------
    */

    if ($action === 'save_event') {

        $id =
            cleanText(
                $_POST['id'] ?? '',
                40
            );

        $date =
            cleanText(
                $_POST['date'] ?? '',
                10
            );

        $name =
            cleanText(
                $_POST['name'] ?? '',
                80
            );

        $startTime =
            cleanText(
                $_POST['start_time'] ?? '',
                5
            );

        $endTime =
            cleanText(
                $_POST['end_time'] ?? '',
                5
            );

        $details =
            cleanText(
                $_POST['details'] ?? '',
                300
            );

        $eventType =
            cleanText(
                $_POST['event_type'] ?? 'single',
                20
            );

        $repeatEnd =
            cleanText(
                $_POST['repeat_end'] ?? '',
                10
            );

        $editScope =
            cleanText(
                $_POST['edit_scope'] ?? 'single',
                20
            );


        /*
         * Validation
         */

        if (!validDateString($date)) {

            $error =
                'Please enter a valid event date.';

        } elseif ($name === '') {

            $error =
                'Please enter an event name.';

        } elseif (
            !validTimeString($startTime) ||
            !validTimeString($endTime)
        ) {

            $error =
                'Please enter valid start and end times.';

        } elseif (
            $startTime === '' &&
            $endTime !== ''
        ) {

            $error =
                'Please select a start time if you select an end time.';

        } elseif (
            $startTime !== '' &&
            $endTime !== '' &&
            $endTime <= $startTime
        ) {

            $error =
                'The end time must be after the start time.';

        } else {

            $time =
                formatEventTime(
                    $startTime,
                    $endTime
                );


            /*
            |--------------------------------------------------------------------------
            | Editing an existing event
            |--------------------------------------------------------------------------
            */

            if ($id !== '') {

                $editIndex = null;
                $editEvent = null;

                foreach (
                    $events as $index => $event
                ) {

                    if (
                        ($event['id'] ?? '') === $id
                    ) {
                        $editIndex = $index;
                        $editEvent = $event;
                        break;
                    }
                }

                if ($editEvent === null) {

                    $error =
                        'The event could not be found.';

                } else {

                    $seriesId =
                        (string) (
                            $editEvent['series_id'] ?? ''
                        );


                    /*
                     * Edit entire recurring series
                     */

                    if (
                        $editScope === 'series' &&
                        $seriesId !== ''
                    ) {

                        foreach (
                            $events as $index => $event
                        ) {

                            if (
                                ($event['series_id'] ?? '') ===
                                $seriesId
                            ) {

                                $events[$index]['name'] =
                                    $name;

                                $events[$index]['start_time'] =
                                    $startTime;

                                $events[$index]['end_time'] =
                                    $endTime;

                                $events[$index]['time'] =
                                    $time;

                                $events[$index]['details'] =
                                    $details;
                            }
                        }

                        sortEvents($events);

                        if (
                            writeJsonFile(
                                $eventsFile,
                                $events
                            )
                        ) {

                            $message =
                                'Recurring series updated.';

                        } else {

                            $error =
                                'The recurring series could not be saved.';
                        }

                    } else {

                        /*
                         * Edit this event only
                         */

                        if (
                            eventAlreadyExists(
                                $events,
                                $date,
                                $name,
                                $id
                            )
                        ) {

                            $error =
                                'An event with this name already exists on that date.';

                        } else {

                            $cancelled =
                                !empty(
                                    $editEvent['cancelled']
                                );

                            $existingSeriesId =
                                (string) (
                                    $editEvent['series_id'] ?? ''
                                );

                            $events[$editIndex] = [
                                'id' => $id,
                                'series_id' =>
                                    $existingSeriesId,
                                'date' => $date,
                                'name' => $name,
                                'start_time' =>
                                    $startTime,
                                'end_time' =>
                                    $endTime,
                                'time' => $time,
                                'details' =>
                                    $details,
                                'cancelled' =>
                                    $cancelled
                            ];

                            sortEvents($events);

                            if (
                                writeJsonFile(
                                    $eventsFile,
                                    $events
                                )
                            ) {

                                $message =
                                    'Event updated.';

                            } else {

                                $error =
                                    'The event could not be saved.';
                            }
                        }
                    }
                }

            } else {

                /*
                |--------------------------------------------------------------------------
                | New single event
                |--------------------------------------------------------------------------
                */

                if ($eventType === 'single') {

                    if (
                        eventAlreadyExists(
                            $events,
                            $date,
                            $name
                        )
                    ) {

                        $error =
                            'An event with this name already exists on that date.';

                    } else {

                        $events[] = [
                            'id' =>
                                makeId(),
                            'series_id' =>
                                '',
                            'date' =>
                                $date,
                            'name' =>
                                $name,
                            'start_time' =>
                                $startTime,
                            'end_time' =>
                                $endTime,
                            'time' =>
                                $time,
                            'details' =>
                                $details,
                            'cancelled' =>
                                false
                        ];

                        sortEvents($events);

                        if (
                            writeJsonFile(
                                $eventsFile,
                                $events
                            )
                        ) {

                            $message =
                                'Single event added.';

                        } else {

                            $error =
                                'The event could not be saved.';
                        }
                    }

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | New recurring series
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !validDateString(
                            $repeatEnd
                        )
                    ) {

                        $error =
                            'Please select a valid recurring end date.';

                    } else {

                        $startDate =
                            new DateTime($date);

                        $endDate =
                            new DateTime(
                                $repeatEnd
                            );

                        if (
                            $endDate <
                            $startDate
                        ) {

                            $error =
                                'The recurring end date must be after the first meeting date.';

                        } else {

                            $seriesId =
                                makeId();

                            $currentDate =
                                clone $startDate;

                            $added = 0;
                            $skipped = 0;

                            while (
                                $currentDate <=
                                $endDate
                            ) {

                                $eventDate =
                                    $currentDate->format(
                                        'Y-m-d'
                                    );

                                if (
                                    eventAlreadyExists(
                                        $events,
                                        $eventDate,
                                        $name
                                    )
                                ) {

                                    $skipped++;

                                } else {

                                    $events[] = [
                                        'id' =>
                                            makeId(),
                                        'series_id' =>
                                            $seriesId,
                                        'date' =>
                                            $eventDate,
                                        'name' =>
                                            $name,
                                        'start_time' =>
                                            $startTime,
                                        'end_time' =>
                                            $endTime,
                                        'time' =>
                                            $time,
                                        'details' =>
                                            $details,
                                        'cancelled' =>
                                            false
                                    ];

                                    $added++;
                                }

                                $currentDate->modify(
                                    '+14 days'
                                );
                            }

                            sortEvents($events);

                            if (
                                writeJsonFile(
                                    $eventsFile,
                                    $events
                                )
                            ) {

                                $message =
                                    $added .
                                    ' recurring event' .
                                    (
                                        $added === 1
                                            ? ''
                                            : 's'
                                    ) .
                                    ' created.';

                                if ($skipped > 0) {

                                    $message .=
                                        ' ' .
                                        $skipped .
                                        ' duplicate' .
                                        (
                                            $skipped === 1
                                                ? ''
                                                : 's'
                                        ) .
                                        ' skipped.';
                                }

                            } else {

                                $error =
                                    'The recurring series could not be saved.';
                            }
                        }
                    }
                }
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Cancel or restore one event
    |--------------------------------------------------------------------------
    */

    if (
        $action === 'cancel_event' ||
        $action === 'restore_event'
    ) {

        $id =
            cleanText(
                $_POST['id'] ?? '',
                40
            );

        $found = false;

        foreach (
            $events as $index => $event
        ) {

            if (
                ($event['id'] ?? '') ===
                $id
            ) {

                $events[$index]['cancelled'] =
                    (
                        $action ===
                        'cancel_event'
                    );

                $found = true;
                break;
            }
        }

        if (!$found) {

            $error =
                'Event could not be found.';

        } elseif (
            writeJsonFile(
                $eventsFile,
                $events
            )
        ) {

            $message =
                (
                    $action ===
                    'cancel_event'
                )
                ? 'Event cancelled.'
                : 'Event restored.';

        } else {

            $error =
                'The event could not be updated.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Delete one event
    |--------------------------------------------------------------------------
    */

    if ($action === 'delete_event') {

        $id =
            cleanText(
                $_POST['id'] ?? '',
                40
            );

        $events =
            array_values(
                array_filter(
                    $events,
                    function ($event) use ($id) {

                        return (
                            ($event['id'] ?? '') !==
                            $id
                        );
                    }
                )
            );

        if (
            writeJsonFile(
                $eventsFile,
                $events
            )
        ) {

            $message =
                'Event deleted.';

        } else {

            $error =
                'The event could not be deleted.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Delete whole recurring series
    |--------------------------------------------------------------------------
    */

    if (
        $action ===
        'delete_series'
    ) {

        $seriesId =
            cleanText(
                $_POST['series_id'] ?? '',
                40
            );

        if ($seriesId === '') {

            $error =
                'This event is not part of a recurring series.';

        } else {

            $beforeCount =
                count($events);

            $events =
                array_values(
                    array_filter(
                        $events,
                        function ($event) use ($seriesId) {

                            return (
                                ($event['series_id'] ?? '') !==
                                $seriesId
                            );
                        }
                    )
                );

            $removed =
                $beforeCount -
                count($events);

            if (
                writeJsonFile(
                    $eventsFile,
                    $events
                )
            ) {

                $message =
                    $removed .
                    ' event' .
                    (
                        $removed === 1
                            ? ''
                            : 's'
                    ) .
                    ' removed from the series.';

            } else {

                $error =
                    'The recurring series could not be deleted.';
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| Reload latest events
|--------------------------------------------------------------------------
*/

$events =
    readJsonFile(
        $eventsFile
    );

sortEvents($events);


/*
|--------------------------------------------------------------------------
| Event being edited
|--------------------------------------------------------------------------
*/

$editId =
    cleanText(
        $_GET['edit'] ?? '',
        40
    );

$editEvent = null;

foreach (
    $events as $event
) {

    if (
        $editId !== '' &&
        ($event['id'] ?? '') ===
        $editId
    ) {

        $editEvent = $event;
        break;
    }
}


/*
|--------------------------------------------------------------------------
| Backwards compatibility for old events
|--------------------------------------------------------------------------
*/

if ($editEvent) {

    if (
        !isset(
            $editEvent['start_time']
        )
    ) {
        $editEvent['start_time'] =
            '';
    }

    if (
        !isset(
            $editEvent['end_time']
        )
    ) {
        $editEvent['end_time'] =
            '';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

  <meta charset="utf-8">

  <meta
    name="viewport"
    content="width=device-width, initial-scale=1"
  >

  <title>
    Manage Calendar | Sugar Code It
  </title>

  <link
    rel="stylesheet"
    href="/styles.css"
  >

  <style>

    .admin-breadcrumb-wrap {
      background: #0d1b2d;
      border-bottom:
        1px solid rgba(255,255,255,0.10);
    }

    .admin-breadcrumb {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 18px 0;
      font-weight: 800;
      flex-wrap: wrap;
    }

    .admin-breadcrumb a,
    .admin-breadcrumb span {
      display: inline-block;
      padding: 9px 16px;
      border-radius: 999px;
      text-decoration: none;
    }

    .admin-breadcrumb a {
      background: #6f86ff;
      border: 1px solid #6f86ff;
      color: #ffffff;
    }

    .admin-breadcrumb a:hover {
      background: #8296ff;
    }

    .admin-breadcrumb .separator {
      padding: 0;
      color: #9fb0c7;
    }

    .admin-breadcrumb .current {
      background: transparent;
      border: 1px solid #6f86ff;
      color: #dce4ff;
    }

    .event-type-box {
      border:
        1px solid rgba(111,134,255,0.30);
      border-radius: 14px;
      padding: 18px;
      margin-bottom: 18px;
      background:
        rgba(111,134,255,0.06);
    }

    .series-options {
      margin-top: 18px;
      padding-top: 18px;
      border-top:
        1px solid rgba(111,134,255,0.22);
    }

    .repeat-note {
      margin-top: 8px;
      font-size: 0.9rem;
      opacity: 0.8;
    }

    .series-badge {
      display: inline-block;
      margin-left: 8px;
      padding: 3px 7px;
      border-radius: 999px;
      background:
        rgba(111,134,255,0.18);
      color: #cbd3ff;
      font-size: 0.68rem;
      font-weight: 800;
      letter-spacing: 0.05em;
      text-transform: uppercase;
    }

    .cancelled-admin-event {
      opacity: 0.68;
    }

    .cancelled-admin-event h3 {
      text-decoration: line-through;
    }

    .admin-actions {
      flex-wrap: wrap;
    }

    .time-grid {
      display: grid;
      grid-template-columns:
        repeat(2, minmax(0,1fr));
      gap: 16px;
    }

    @media (max-width: 650px) {

      .time-grid {
        grid-template-columns: 1fr;
      }

    }

  </style>

</head>


<body class="admin-body">


<header class="nav">

  <div class="container navin">

    <a
      class="brand"
      href="/index.html"
    >

      <img
        src="/assets/logo.png"
        alt="Sugar Code It logo"
      >

      <span>
        Sugar Code It
      </span>

    </a>


    <div class="admin-nav-note">
      Calendar management
    </div>

  </div>

</header>


<div class="admin-breadcrumb-wrap">

  <div
    class="container admin-breadcrumb"
    aria-label="Admin breadcrumb"
  >

    <a href="/admin/">
      Admin Dashboard
    </a>

    <span class="separator">
      /
    </span>

    <span class="current">
      Calendar
    </span>

  </div>

</div>


<main>


<section class="page-hero admin-hero">

  <div class="container">

    <span class="eyebrow">
      Calendar
    </span>

    <h1>
      Manage calendar.
    </h1>

    <p>
      Add single events or create an entire
      biweekly series. Recurring events can
      later be edited individually or together.
    </p>


    <div class="form-actions">

      <a
        class="button secondary"
        href="/admin/"
      >
        Back to Admin
      </a>

      <a
        class="button secondary"
        href="/calendar.html"
        target="_blank"
        rel="noopener"
      >
        View Public Calendar
      </a>

    </div>

  </div>

</section>


<section>

  <div class="container">


    <?php if ($message !== ''): ?>

      <div class="admin-message success">
        <?php echo h($message); ?>
      </div>

    <?php endif; ?>


    <?php if ($error !== ''): ?>

      <div class="admin-message error">
        <?php echo h($error); ?>
      </div>

    <?php endif; ?>


    <div class="admin-panel">


      <div class="section-head compact">

        <div>

          <span class="eyebrow">
            Event editor
          </span>

          <h2>

            <?php
            echo $editEvent
                ? 'Edit event'
                : 'Add event';
            ?>

          </h2>

        </div>

      </div>


      <form
        class="editor-form admin-form"
        method="post"
      >


        <input
          type="hidden"
          name="csrf"
          value="<?php
          echo h(
              $_SESSION['csrf']
          );
          ?>"
        >


        <input
          type="hidden"
          name="action"
          value="save_event"
        >


        <input
          type="hidden"
          name="id"
          value="<?php
          echo h(
              $editEvent['id'] ?? ''
          );
          ?>"
        >


        <?php if (!$editEvent): ?>


          <div class="event-type-box">

            <label>

              Event type

              <select
                name="event_type"
                id="eventType"
              >

                <option value="single">
                  Single event
                </option>

                <option value="series">
                  Recurring series
                </option>

              </select>

            </label>


            <div
              class="series-options"
              id="seriesOptions"
              style="display:none;"
            >

              <label>

                Repeat

                <select
                  name="repeat_interval"
                >

                  <option value="14">
                    Every 2 weeks
                  </option>

                </select>

              </label>


              <label>

                Repeat until

                <input
                  type="date"
                  name="repeat_end"
                  id="repeatEnd"
                >

              </label>


              <p class="repeat-note">

                Every meeting in the series
                will be created immediately
                at 14-day intervals.

              </p>

            </div>

          </div>


        <?php endif; ?>


        <label>

          <?php
          echo $editEvent
              ? 'Date'
              : 'Date / first meeting';
          ?>

          <input
            type="date"
            name="date"
            required
            value="<?php
            echo h(
                $editEvent['date'] ?? ''
            );
            ?>"
          >

        </label>


        <label>

          Event name

          <input
            type="text"
            name="name"
            required
            maxlength="80"
            value="<?php
            echo h(
                $editEvent['name'] ?? ''
            );
            ?>"
            placeholder="Club Meeting"
          >

        </label>


        <div class="time-grid">


          <label>

            Start time

            <input
              type="time"
              name="start_time"
              value="<?php
              echo h(
                  $editEvent['start_time']
                  ?? ''
              );
              ?>"
            >

          </label>


          <label>

            End time

            <input
              type="time"
              name="end_time"
              value="<?php
              echo h(
                  $editEvent['end_time']
                  ?? ''
              );
              ?>"
            >

          </label>


        </div>


        <label>

          Details

          <textarea
            name="details"
            maxlength="300"
            rows="4"
            placeholder="Room, activity, supplies, announcement..."
          ><?php
          echo h(
              $editEvent['details'] ?? ''
          );
          ?></textarea>

        </label>


        <?php if (
            $editEvent &&
            !empty(
                $editEvent['series_id']
            )
        ): ?>


          <div class="event-type-box">

            <label>

              Apply changes to

              <select name="edit_scope">

                <option value="single">
                  This event only
                </option>

                <option value="series">
                  Entire recurring series
                </option>

              </select>

            </label>


            <p class="repeat-note">

              "This event only" lets you make
              an exception for one meeting.

              "Entire recurring series" changes
              the event name, start time,
              end time and details for all
              meetings in this series.

              The dates of the series remain
              unchanged.

            </p>

          </div>


        <?php endif; ?>


        <div class="form-actions">


          <button
            class="button"
            type="submit"
          >

            <?php

            if ($editEvent) {

                echo 'Update Event';

            } else {

                echo 'Add Event';

            }

            ?>

          </button>


          <?php if ($editEvent): ?>

            <a
              class="button secondary"
              href="/admin/calendar.php"
            >
              Cancel Editing
            </a>

          <?php endif; ?>


        </div>


      </form>

    </div>

  </div>

</section>


<section class="light-section">

  <div class="container">


    <div class="section-head">

      <div>

        <span class="eyebrow">
          Calendar
        </span>

        <h2>
          Current events
        </h2>

      </div>

    </div>


    <?php if (
        count($events) === 0
    ): ?>


      <div class="empty-state">

        <h3>
          No events yet
        </h3>

        <p>
          Add your first event above.
        </p>

      </div>


    <?php else: ?>


      <div class="admin-list">


        <?php foreach (
            $events as $event
        ): ?>


          <article
            class="admin-list-item <?php
            echo !empty(
                $event['cancelled']
            )
                ? 'cancelled-admin-event'
                : '';
            ?>"
          >


            <div>


              <div class="card-meta">

                <?php
                echo h(
                    $event['date'] ?? ''
                );
                ?>


                <?php
                if (
                    !empty(
                        $event['time']
                    )
                ) {
                    echo ' | ' .
                    h(
                        $event['time']
                    );
                }
                ?>


                <?php if (
                    !empty(
                        $event['series_id']
                    )
                ): ?>

                  <span class="series-badge">
                    Recurring
                  </span>

                <?php endif; ?>


              </div>


              <h3>

                <?php if (
                    !empty(
                        $event['cancelled']
                    )
                ): ?>

                  CANCELLED —

                <?php endif; ?>


                <?php
                echo h(
                    $event['name']
                    ?? 'Event'
                );
                ?>

              </h3>


              <?php if (
                  !empty(
                      $event['details']
                  )
              ): ?>

                <p>

                  <?php
                  echo h(
                      $event['details']
                  );
                  ?>

                </p>

              <?php endif; ?>


            </div>


            <div class="admin-actions">


              <a
                class="small-button"
                href="/admin/calendar.php?edit=<?php
                echo urlencode(
                    (string) (
                        $event['id'] ?? ''
                    )
                );
                ?>"
              >
                Edit
              </a>


              <form method="post">

                <input
                  type="hidden"
                  name="csrf"
                  value="<?php
                  echo h(
                      $_SESSION['csrf']
                  );
                  ?>"
                >

                <input
                  type="hidden"
                  name="action"
                  value="<?php
                  echo !empty(
                      $event['cancelled']
                  )
                      ? 'restore_event'
                      : 'cancel_event';
                  ?>"
                >

                <input
                  type="hidden"
                  name="id"
                  value="<?php
                  echo h(
                      $event['id'] ?? ''
                  );
                  ?>"
                >

                <button
                  class="small-button"
                  type="submit"
                >

                  <?php

                  echo !empty(
                      $event['cancelled']
                  )
                      ? 'Restore'
                      : 'Cancel Event';

                  ?>

                </button>

              </form>


              <form
                method="post"
                onsubmit="
                  return confirm(
                    'Delete this event permanently?'
                  );
                "
              >

                <input
                  type="hidden"
                  name="csrf"
                  value="<?php
                  echo h(
                      $_SESSION['csrf']
                  );
                  ?>"
                >

                <input
                  type="hidden"
                  name="action"
                  value="delete_event"
                >

                <input
                  type="hidden"
                  name="id"
                  value="<?php
                  echo h(
                      $event['id'] ?? ''
                  );
                  ?>"
                >

                <button
                  class="small-button danger-text"
                  type="submit"
                >
                  Delete
                </button>

              </form>


              <?php if (
                  !empty(
                      $event['series_id']
                  )
              ): ?>


                <form
                  method="post"
                  onsubmit="
                    return confirm(
                      'Delete the ENTIRE recurring series? This will remove every meeting in this series.'
                    );
                  "
                >

                  <input
                    type="hidden"
                    name="csrf"
                    value="<?php
                    echo h(
                        $_SESSION['csrf']
                    );
                    ?>"
                  >

                  <input
                    type="hidden"
                    name="action"
                    value="delete_series"
                  >

                  <input
                    type="hidden"
                    name="series_id"
                    value="<?php
                    echo h(
                        $event['series_id']
                    );
                    ?>"
                  >

                  <button
                    class="small-button danger-text"
                    type="submit"
                  >
                    Delete Series
                  </button>

                </form>


              <?php endif; ?>


            </div>


          </article>


        <?php endforeach; ?>


      </div>


    <?php endif; ?>


  </div>

</section>


</main>


<footer class="footer">

  <div class="container footer-grid">


    <div>

      <strong style="color:white">
        Sugar Code It
      </strong>

      <br>

      Private calendar management

    </div>


    <div>

      <a href="/admin/">
        Back to Admin
      </a>

    </div>


    <div>

      <a href="/calendar.html">
        Public Calendar
      </a>

    </div>


  </div>

</footer>


<script>

  var eventType =
    document.getElementById(
      'eventType'
    );

  var seriesOptions =
    document.getElementById(
      'seriesOptions'
    );

  var repeatEnd =
    document.getElementById(
      'repeatEnd'
    );


  function updateEventType() {

    if (
      !eventType ||
      !seriesOptions
    ) {
      return;
    }


    if (
      eventType.value ===
      'series'
    ) {

      seriesOptions.style.display =
        'block';

      if (repeatEnd) {
        repeatEnd.required = true;
      }

    } else {

      seriesOptions.style.display =
        'none';

      if (repeatEnd) {

        repeatEnd.required = false;
        repeatEnd.value = '';

      }

    }

  }


  if (eventType) {

    eventType.addEventListener(
      'change',
      updateEventType
    );

    updateEventType();

  }

</script>


</body>

</html>