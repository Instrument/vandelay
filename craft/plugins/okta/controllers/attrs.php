<?php

session_start();

if (craft()->httpSession->get('auth')['samlUserdata'] != null) {
    if (craft()->httpSession->get('auth')['samlUserdata']) {
        $attributes = craft()->httpSession->get('auth')['samlUserdata'];
        echo 'You have the following attributes:<br>';
        echo '<table><thead><th>Name</th><th>Values</th></thead><tbody>';
        foreach ($attributes as $attributeName => $attributeValues) {
            echo '<tr><td>' . htmlentities($attributeName) . '</td><td><ul>';
            foreach ($attributeValues as $attributeValue) {
                echo '<li>' . htmlentities($attributeValue) . '</li>';
            }
            echo '</ul></td></tr>';
        }
        echo '</tbody></table>';
    } else {
        echo "<p>You don't have any attribute</p>";
    }

    echo '<p><a href="index.php?slo" >Logout</a></p>';
} else {
    echo '<p><a href="index.php?sso2" >Login and access later to this page</a></p>';
}
