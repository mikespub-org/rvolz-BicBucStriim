Feature: Clip thumbnails

  An admin can change whether generated thumbnails are clipped.

  Background: Navigating to the configuration page
    When I click on menu item "Home"
    And the app switches to the "Home" view
    And I click on menu item "Admin"
    And the app switches to the "Admin" view
    And I click on menu item "Configuration"
    And the app switches to the "Configuration" view

  Scenario: Save the thumbnail clipping setting
    When I set the "thumb_gen_clipped" slider to "No"
    And I save the configuration
    Then the "thumb_gen_clipped" slider is set to "No"
    When I reload the configuration page
    Then the "thumb_gen_clipped" slider is set to "No"
    # Actions: AdminActions::configuration, GET /admin/configuration/ (admin-config); AdminActions::changeJson, POST /admin/configuration/ (admin-config-post).
    # The PHPUnit configuration tests cover page-size persistence and validation, not persistence of this thumbnail setting.
