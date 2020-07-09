<?php
/*--------------------------------------*/
/* DocWire Latest Posts Admin page by CG
/*--------------------------------------*/
function docwire_posts() {
    add_menu_page (
        'Docwire Posts',
        'Docwire Latest Posts',
        'manage_options',
        'dw-latest-posts',
        'docwire_get_posts',
        'dashicons-networking',
        0
    );
}
add_action('admin_menu', 'docwire_posts');


/*--------------------------------------*/
/* Get latest Posts from DW by CG
/*--------------------------------------*/
function docwire_get_posts() {
    //$todays_dw_posts = new DateTime('now', new DateTimeZone('America/New_York'));
    $todays_dw_posts = date('Y-m-d H:i:s');
    //echo $todays_dw_posts->format('F j, Y, g:i a');
    $response = wp_remote_get(add_query_arg(array(
        'date_query' => array(
                'after' => $todays_dw_posts),
                'per_page' => 2

    ), 'http://docwirenews.com/wp-json/wp/v2/posts?categories=2810'));

    $remote_posts = json_decode($response['body']); // our posts are here

    if (!is_wp_error($response) && $response['response']['code'] == 200) {
        /* categories to map */
        /*  2807, 2810, 77, 13243 */
        /* tags from DW 65610,2899,92809,2847,8477,92808 */
        $hemCategories = array(2810); //CNT hematology categories
        $mappedCats = array();
        $postTags = array();
        $getUsers = get_users();
        $usersArray = array();


        foreach ($getUsers as $usr) {
            array_push($usersArray, $usr->ID);
        }
        ?>

        <h1>Docwire Oncology Picks</h1>
        <table style="width: 100%; background-color: #ffffff; padding: 10px; font-family: 'Century Gothic';">

            <tr style="text-align: left; font-family: 'Century Gothic'; font-size: 18px; line-height: 2; ">

                <th style="width: 3%;padding: 8px 15px;">Date</th>
                <th style="width: 8%;padding: 8px 15px;">Post Image</th>
                <th style="width: 25%;padding: 8px 15px;">Title</th>
                <th style="width: 30%;padding: 8px 15px;">Content</th>
                <th style="width: 10%; ">DW Post link</th>
            </tr>
            <?php

            foreach ($remote_posts as $remote_post) {


                // Retrieve Author
                $postAuthor = (in_array($remote_post->author, $usersArray)) ? $remote_post->author : 36;

                // Retrieve DW Post Tags
                foreach ($remote_post->tags as $remTag) {
                    $response = wp_remote_get('http://docwirenews.com/wp-json/wp/v2/tags/' . $remTag);
                    $remote_tags = json_decode($response['body']); // our tags are here
                    foreach ($remote_tags as $tag) {
                        array_push($postTags, $tag->name);
                    }
                }

                foreach ($remote_post->categories as $remCat) {
                    if (in_array($remCat, $hemCategories)) {
                        array_push($mappedCats, $remCat);
                    }
                }
                        if ($_GET['insert_content'] && $_GET['insert_content'] == 'yes') {


                            $inserted_post = wp_insert_post([
                                'post_title' => $remote_post->title->rendered,
                                'post_content' => $remote_post->content->rendered,
                                'post_author' => $postAuthor,
                                'post_type' => 'post',
                                'post_status' => 'publish',
                                'post_date' => $remote_post->date,
                                'post_category' => $mappedCats,
                                'tags_input' => $postTags
                            ]);


                            // Adding Featured Image to Post
                            $image_url = $remote_post->fi_350x250; // Define the image URL here
                            $image_name = $remote_post->title->rendered . '.png';
                            $upload_dir = wp_upload_dir(); // Set upload folder
                            $image_data = file_get_contents($image_url); // Get image data
                            $unique_file_name = wp_unique_filename($upload_dir['path'], $image_name); // Generate unique name
                            $filename = basename($unique_file_name); // Create image file name

                            // Check folder permission and define file location
                            if (wp_mkdir_p($upload_dir['path'])) {
                                $file = $upload_dir['path'] . '/' . $filename;
                            } else {
                                $file = $upload_dir['basedir'] . '/' . $filename;
                            }

                            // Create the image  file on the server
                            file_put_contents($file, $image_data);

                            // Check image file type
                            $wp_filetype = wp_check_filetype($filename, null);

                            // Set attachment data
                            $attachment = array(
                                'post_mime_type' => $wp_filetype['type'],
                                'post_title' => sanitize_file_name($filename),
                                'post_content' => '',
                                'post_status' => 'inherit'
                            );

                            // Create the attachment
                            $attach_id = wp_insert_attachment($attachment, $file, $inserted_post);

                            // Include image.php
                            require_once(ABSPATH . 'wp-admin/includes/image.php');

                            // Define attachment metadata
                            $attach_data = wp_generate_attachment_metadata($attach_id, $file);

                            // Assign metadata to attachment
                            wp_update_attachment_metadata($attach_id, $attach_data);

                            // And finally assign featured image to post
                            set_post_thumbnail($inserted_post, $attach_id);





                            ?>

                            <tr>
                                <td></td>
                                <td>

                                    <img style="width: 100px !important; border-radius: 50%;"
                                         src="<?php echo wp_get_attachment_url(get_post_thumbnail_id($inserted_post), 'thumbnail'); ?>"/>

                                </td>
                                <td>
                                    <h2><?php echo get_the_title($inserted_post); ?></h2>

                                </td>
                                <td>
                                    <p><?php echo get_the_excerpt($inserted_post); ?></p>

                                </td>
                                <td>
                                    <a href="<?php echo $remote_post->link; ?>" target="_blank">Read More</a>
                                </td>
                            </tr>


                            <?php

                        }else {

                            // echo $inserted_post; // this will give the inserted post ID

                            ?>
                            <tr>


                                <td style="width: 3%;padding: 8px 10px;">
                                    <?php echo $remote_post->date; ?>
                                </td>

                                <td style="width: 8%;padding: 8px 15px;">

                                    <img style="width: 100px !important;"
                                         src="<?php echo $remote_post->fi_small; ?>"/>

                                </td>
                                <td style="width: 25%;padding: 8px 15px;">
                                    <h2><?php echo $remote_post->title->rendered; ?></h2>
                                </td>
                                <td style="width: 30%;padding: 8px 15px;">
                                    <p><?php echo $remote_post->excerpt->rendered ?></p>
                                </td>
                                <td style="width: 10%; ">

                                    <a style="background-color: #ee7411; color: #ffffff;padding: 8px 12px; text-decoration: none;"
                                       href="<?php echo $remote_post->link; ?>" target="_blank">Original Source</a>
                                </td>
                            </tr>

                            <?php

                        }

            }
            ?>

        </table>
        <?php
    }


    $todays_dw_posts = date('Y-m-d H:i:s');
    $response = wp_remote_get(add_query_arg(array(
        'date_query' => array(
            'after' => $todays_dw_posts),
            'per_page' => 3

    ), 'http://docwirenews.com/wp-json/wp/v2/posts?categories=13243'));

    $remote_posts = json_decode($response['body']); // our posts are here

    if (!is_wp_error($response) && $response['response']['code'] == 200) {
        /* categories to map */
        /*  2807, 2810, 77, 13243 */
        /* tags from DW 65610,2899,92809,2847,8477,92808 */
        $hemCategories = array(13243, 77); //CNT hematology categories
        $mappedCats = array();
        $postTags = array();
        $getUsers = get_users();
        $usersArray = array();


        foreach ($getUsers as $usr) {
            array_push($usersArray, $usr->ID);
        }
        ?>


        <table style="width: 100%; background-color: #ffffff; padding: 10px; font-family: 'Century Gothic';">
        <h1>Abstracts</h1>

            <?php

            foreach ($remote_posts as $remote_post) {


                // Retrieve Author
                $postAuthor = (in_array($remote_post->author, $usersArray)) ? $remote_post->author : 36;

                // Retrieve DW Post Tags
                foreach ($remote_post->tags as $remTag) {
                    $response = wp_remote_get('http://docwirenews.com/wp-json/wp/v2/tags/' . $remTag);
                    $remote_tags = json_decode($response['body']); // our tags are here
                    foreach ($remote_tags as $tag) {
                        array_push($postTags, $tag->name);
                    }
                }

                foreach ($remote_post->categories as $remCat) {
                    if (in_array($remCat, $hemCategories)) {
                        array_push($mappedCats, $remCat);
                    }
                }
                if ($_GET['insert_content'] && $_GET['insert_content'] == 'yes') {


                    $inserted_post = wp_insert_post([
                        'post_title' => $remote_post->title->rendered,
                        'post_content' => $remote_post->content->rendered,
                        'post_author' => $postAuthor,
                        'post_type' => 'post',
                        'post_status' => 'publish',
                        'post_date' => $remote_post->date,
                        'post_category' => $mappedCats,
                        'tags_input' => $postTags
                    ]);




                    ?>

                    <tr>
                        <td></td>
                        <td>

                            <img style="width: 100px !important; border-radius: 50%;"
                                 src="<?php echo wp_get_attachment_url(get_post_thumbnail_id($inserted_post), 'thumbnail'); ?>"/>

                        </td>
                        <td>

                            <h2><?php  echo get_the_title($inserted_post); ?></h2>

                        </td>
                        <td>
                            <p><?php echo get_the_excerpt($inserted_post); ?></p>

                        </td>
                        <td>
                            <a href="<?php echo $remote_post->link; ?>" target="_blank">Read More</a>
                        </td>
                    </tr>


                    <?php

                }else {

                    // echo $inserted_post; // this will give the inserted post ID
                    ?>
                    <tr>


                        <td style="width: 3%;padding: 8px 10px;">
                            <?php echo $remote_post->date;?>
                        </td>

                        <td style="width: 8%;padding: 8px 15px;">

                            <img style="width: 100px !important;"
                                 src="<?php echo $remote_post->fi_small; ?>"/>

                        </td>
                        <td style="width: 25%;padding: 8px 15px;">
                            <h2><?php echo $remote_post->title->rendered; ?></h2>
                        </td>
                        <td style="width: 30%;padding: 8px 15px;">
                            <p><?php echo $remote_post->excerpt->rendered ?></p>
                        </td>
                        <td style="width: 10%; ">

                            <a style="background-color: #ee7411; color: #ffffff;padding: 8px 12px; text-decoration: none;"
                               href="<?php echo $remote_post->link; ?>" target="_blank">Original Source</a>
                        </td>
                    </tr>

                    <?php

                }

            }
            ?>


            <tr>

                <td stle="width: 100%;">
                    <br><br>
                    <a style="background-color: #ee7411; color: #ffffff;padding: 5px 8px; font-size: 14px; text-decoration: none; "
                       href="?page=dw-latest-posts&insert_content=yes">Publish</a>
                </td>
            </tr>
        </table>
        <?php
    }


}
