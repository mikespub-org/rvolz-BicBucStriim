Feature: With Send-to-Kindle books can be mailed.
	# Client flow: script.js opens #kindlePopup, submits #ajax-form by XHR, and remembers the email.
	# No tests/js harness currently covers the Kindle client flow.

	If Send-to-Kindle is activated in the configuration,
	and a book has Kindle-compatible formats,
	the book can also be mailed instead of downloading it.

	Scenario: Book has no compatible formats
		When I click on menu item "Books"
		And the app switches to the "Books" view
		And I click on list item "Glücksritter"
		And the "Books" details page 4 for "Die Glücksritter" appears
		Then there are no "Send-to-Kindle" options
		# Action: MainActions::title, GET /titles/{id}/ (main-title); option visibility is rendered by title_detail.twig.
		# No PHPUnit test currently asserts this template condition.

	Scenario: Book has compatible formats
		When I click on menu item "Books"
		And the app switches to the "Books" view
		And I click on list item "Lob der Faulheit"
		And the "Books" details page 1 for "Lob der Faulheit" appears
		And I open the "Send-to-Kindle" form
		And I enter the recipient email "reader@example.org"
		And I submit the form
		Then I get the message "The book has been sent!"
		# Actions: MainActions::title, GET /titles/{id}/ (main-title); MainActions::kindle, POST /titles/{id}/kindle/{file} (main-kindle).
		# Request coverage: tests/Actions/MainActionsTest.php::testMainKindle.
		# No PHPUnit test currently asserts the template option visibility.
	
