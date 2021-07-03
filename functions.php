<?php
add_action('acf/save_post', 'discord_update_notification', 5);

function discord_update_notification($post_ID)
{

    // Get post name and featured image
    $post_title = html_entity_decode(get_the_title($post_ID));
    $featured = get_the_post_thumbnail_url($post_ID);

    // Default image if there's no featured
    if (!$featured)
    {
        $featured = "[YOUR IMAGE ADDRESS]";
    }
  
    // Your Webhook URL
    $webhookurl = "[YOUR DISCORD WEBHOOK URL]";

    // Permalink
    $post_link = get_the_permalink($post_ID);

    // Post type and its singular name
    $post_type = get_post_type($post_ID);
    $post_type_object = get_post_type_object($post_type);
    $post_type_string = $post_type_object
        ->labels->singular_name;

    // Get author
    $current_user = wp_get_current_user();
    $author = esc_html($current_user->display_name);

    $action = "";
    $action_footer = "";
    $string = "";
    $hexcolor = "3366ff"; // This colour isn't used, it's just here as a fallback
    // Get previous post values.
    $prev_values = array_values(get_fields($post_ID, false));

    // Get submitted post values.
    $submitted = $_POST['acf'];
    $keys = array_keys($submitted);
    $values = array_values($submitted);

    // Check differences between arrays
    $differences = array_map('json_decode', array_diff(array_map('json_encode', $values) , array_map('json_encode', $prev_values)));

    // If it's a new post
    if (empty($prev_values))
    {
        $action = "Added"; // First Embed Field header/name
        $action_footer = "created"; // Footer verb
        $hexcolor = "97ba46"; // Embed colour for new posts
        // Just loop through each field and output the ones that aren't null/empty
        foreach ($submitted as $field => $value)
        {
            // If the field's value is an array, it's a field group so we iterate through its fields
            if (!is_array($value))
            {
                if (!empty($value))
                {
                    $updated_field = get_field_object($field);
                    $string .= $updated_field['label'] . "\n";
                }
            }
            // If it's not a field group, just take the field as is
            else
            {
                $isempty = true;
                foreach ($value as $subfield => $subvalue)
                {
                    if (!empty($subvalue))
                    {
                        $isempty = false;
                    }
                }
                if ($isempty == false)
                {
                    $updated_field = get_field_object($field);
                    $string .= $updated_field['label'] . "\n";
                }
            }
        }
    }
    // If it's an update to a post
    else
    {
        $action = "Updated"; // First Embed Field header/name
        $action_footer = "modified"; // Footer verb
        $hexcolor = "fdc558"; // Embed colour when updating
        // Loop through the differences
        foreach ($differences as $key => $difference)
        {
            // Check if the field that's flagged as different has an array as its value
            if (is_array($values[$key]))
            {
                // Loop through the field's array value
                foreach ($values[$key] as $field => $value)
                {
                    // Get the equivalent field from the $prev_values object
                    $prev_value = $prev_values[$key][$field];

                    // If the field's value is not an array, escape the string
                    if (!is_array($prev_value))
                    {
                        $prev_value = addslashes($prev_value);
                    }

                    // Compare the $prev_value's field to the new value, and if it is indeed different, append it to the output
                    if ($value != $prev_value)
                    {
                        $updated_field = get_field_object($field);
                        $string .= $updated_field['label'] . "|";
                    }
                }
            }
            // If it's not a field group, just compare the field as is
            else
            {
                $prev_value = $prev_values[$key];
                $value = $values[$key];

                // If the field's value is not an array, escape the string
                if (!is_array($prev_value))
                {
                    $prev_value = addslashes($prev_value);
                }

                // Compare the $prev_value's field to the new value, and if it is indeed different, append it to the output
                if ($value != $prev_value)
                {
                    $updated_field = get_field_object($keys[$key]);
                    $string .= $updated_field['label'] . "|";
                }
            }
        }
    }

    // Build the embed
    $embed_fields = [];

    // Check the length of the changed field list to determine if it needs to be broken into separate embed field values (max 1024 characters each)
    $length = strlen($string);
    $blocks = ceil($length / 1024);

    // If there are at least 1024 characters
    if ($blocks > 1)
    {
        // Separate the field list string into an array
        $chunks = explode("|", $string);

        // Divide the array into n chunks, where n is the number of embed fields we need
        $pieces = array_chunk($chunks, ceil(count($chunks) / $blocks));
        $first = true;
        $field_name = "";

        // Loop through each chunk
        foreach ($pieces as $piece)
        {
            // If it's the first chunk, we set the embed field name as Updated/Added
            if ($first)
            {
                $field_name = $action;
                $first = false;
            }
            // Otherwise we make it an invisible space
            else
            {
                $unicodeChar = '\u200b';
                $field_name = json_decode('"' . $unicodeChar . '"');
            }

            // Combine the chunk back into a string
            $chunk = implode("\n", $piece);

            // Add to our array of embed fields to pass to our embed definition
            array_push($embed_fields, array(
                "name" => $field_name,
                "value" => $chunk,
                "inline" => true
            ));
        }
    }
    // If less than 1024 characters, just output the string as one embed field
    else
    {
        $finalstring = str_replace("|", "\n", $string);
        array_push($embed_fields, array(
            "name" => $action,
            "value" => $finalstring,
            "inline" => true
        ));
    }

    // Webhook and Embed settings
    $json_data = json_encode([
    // Message
    "content" => "",

    // Username
    "username" => "Nari",

    // Text-to-speech
    "tts" => false,

    // Embeds Array
    "embeds" => [[
    // Embed Title
    "title" => $post_title,

    // Embed Type
    "type" => "rich",

    // Embed Description
    "description" => "",

    // URL of the Title link
    "url" => $post_link,

    // Embed left border color in HEX
    "color" => hexdec($hexcolor) ,

    // Footer
    "footer" => ["text" => "Page " . $action_footer . " by " . $author
    //	"icon_url" => "https://#/image.png"
    ],

    // If you want a big image, otherwise use Thumbnail
    // "image" => [
    // 	 "url" => "https://#/image.png"
    // ],
    // Thumbnail (Goes to the right of the Title)
    "thumbnail" => ["url" => $featured],

    // Author section (Goes above the Title)
    "author" => ["name" => $post_type_string],

    // Our field array that has the output strings for our changed fields
    "fields" => $embed_fields]]

    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    // Send the message through the Webhook
    $ch = curl_init($webhookurl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-type: application/json'
    ));
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $response = curl_exec($ch);
    curl_close($ch);

}
?>
