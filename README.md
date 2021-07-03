# Notify Discord When WordPress Posts Get Changed
A function that sends notifications to your Discord webhook whenever a WordPress post is saved with modified ACF fields.

![image](https://user-images.githubusercontent.com/15660268/124359839-add73f00-dbf4-11eb-9358-c341840850a6.png)

## Instructions
Make sure you're using ACF, as this function is made to hook into the `acf/save_post` action and compares custom fields.

To implement, just copy the contents of the `functions.php` file into your active theme's `functions.php` file. It is advised to do this using a child theme.
