<?php

header(
    'Content-Type: application/json; charset=UTF-8'
);

header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
);


$photosFile =
    dirname(__DIR__) .
    '/data/photos.json';


if (!is_file($photosFile)) {

    echo json_encode([]);
    exit;
}


$json =
    file_get_contents(
        $photosFile
    );


if ($json === false) {

    echo json_encode([]);
    exit;
}


$photos =
    json_decode(
        $json,
        true
    );


if (!is_array($photos)) {

    echo json_encode([]);
    exit;
}


$section =
    strtolower(
        trim(
            (string) (
                $_GET['section']
                ?? ''
            )
        )
    );


$year =
    trim(
        (string) (
            $_GET['year']
            ?? ''
        )
    );


/*
|--------------------------------------------------------------------------
| Filter by section
|--------------------------------------------------------------------------
*/

if ($section !== '') {

    $photos =
        array_values(
            array_filter(
                $photos,
                function ($photo) use ($section) {

                    return (
                        strtolower(
                            trim(
                                (string) (
                                    $photo['section']
                                    ?? ''
                                )
                            )
                        )
                        ===
                        $section
                    );
                }
            )
        );
}


/*
|--------------------------------------------------------------------------
| Optional year filter
|--------------------------------------------------------------------------
*/

if ($year !== '') {

    $photos =
        array_values(
            array_filter(
                $photos,
                function ($photo) use ($year) {

                    return (
                        (string) (
                            $photo['year']
                            ?? ''
                        )
                        ===
                        $year
                    );
                }
            )
        );
}


/*
|--------------------------------------------------------------------------
| Sort
|--------------------------------------------------------------------------
*/

usort(
    $photos,
    function ($a, $b) {

        $sectionA =
            (string) (
                $a['section']
                ?? ''
            );

        $sectionB =
            (string) (
                $b['section']
                ?? ''
            );


        /*
         * Newest Team year first
         */

        if (
            $sectionA === 'team' &&
            $sectionB === 'team'
        ) {

            $yearA =
                (string) (
                    $a['year']
                    ?? ''
                );

            $yearB =
                (string) (
                    $b['year']
                    ?? ''
                );


            if (
                $yearA !==
                $yearB
            ) {

                return strcmp(
                    $yearB,
                    $yearA
                );
            }
        }


        $positionA =
            (int) (
                $a['position']
                ?? 999999
            );


        $positionB =
            (int) (
                $b['position']
                ?? 999999
            );


        if (
            $positionA ===
            $positionB
        ) {

            return strcmp(
                (string) (
                    $a['uploaded']
                    ?? ''
                ),
                (string) (
                    $b['uploaded']
                    ?? ''
                )
            );
        }


        return
            $positionA <=>
            $positionB;
    }
);


echo json_encode(
    $photos,
    JSON_UNESCAPED_SLASHES |
    JSON_UNESCAPED_UNICODE
);

exit;