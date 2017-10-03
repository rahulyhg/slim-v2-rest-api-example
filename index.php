<?php
// Autoload Dependencies and classes
require_once 'vendor/autoload.php';

// Use RedBean
use \RedBeanPHP\R as R;

// set up database connection
R::setup('mysql:host=localhost;dbname=slim','root','');
R::freeze(true);

// Initialize app
$app = new \Slim\Slim();

/**
 * Set default conditions for route parameters
 * Sanitizes user input with a regex.
 */
\Slim\Route::setDefaultConditions(array(
  'id' => '[0-9]{1,}',
));

class ResourceNotFoundException extends Exception {}

// Route middleware for simple API authentication
function authenticate(\Slim\Route $route) {
    $app = \Slim\Slim::getInstance();
    $uid = $app->getEncryptedCookie('uid');
    $key = $app->getEncryptedCookie('key');
    if (validateUserKey($uid, $key) === false) {
      $app->halt(401);
    }
}

function validateUserKey($uid, $key) {
  // insert your (hopefully more complex) validation routine here
  if ($uid == 'demo' && $key == 'demo') {
    return true;
  } else {
    return false;
  }
}

/**
 * Creates the table articles and adds 2 dummy entries to test.
 */
$app->get('/create-table', function () use ($app) {
    R::exec('
        CREATE TABLE IF NOT EXISTS `articles` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `title` text NOT NULL,
          `url` text NOT NULL,
          `date` date NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8
        ');

    R::exec("
    INSERT IGNORE INTO `articles` (`id`, `title`, `url`, `date`) VALUES
    (1, 'Search and integrate Google+ activity streams with PHP applications',
    'http://www.ibm.com/developerworks/xml/library/x-googleplusphp/index.html', '2012-07-10');

    INSERT IGNORE INTO `articles` (`id`, `title`, `url`, `date`) VALUES
    (2, 'Getting Started with Zend Server CE',
    'http://devzone.zend.com/1389/getting-started-with-zend-server-ce/', '2009-03-02')
        ");
});

/**
 * Generates a temporary API key using cookies valid for 5 minutes.
 * Call this first to gain access to protected API methods
 */
$app->get('/demo', function () use ($app) {
  try {
    $app->setEncryptedCookie('uid', 'demo', '5 minutes');
    $app->setEncryptedCookie('key', 'demo', '5 minutes');
  } catch (Exception $e) {
    $app->response()->status(400);
    $app->response()->header('X-Status-Reason', $e->getMessage());
  }
});

// Handle GET requests for /articles.
$app->get('/articles', 'authenticate', function () use ($app) {
  // query database for all articles
  $articles = R::find('articles');

  // send response header for JSON content type
  $app->response()->header('Content-Type', 'application/json');

  // return JSON-encoded response body with query results
  echo json_encode(R::exportAll($articles));
});

// Handle GET requests for /articles/:id
$app->get('/articles/:id', 'authenticate', function ($id) use ($app) {
  try {
    // query database for single article
    $article = R::findOne('articles', 'id=?', array($id));

    if ($article) {
      // if found, return JSON response
      $app->response()->header('Content-Type', 'application/json');
      echo json_encode(R::exportAll($article));
    } else {
      // else throw exception
      throw new ResourceNotFoundException();
    }
  } catch (ResourceNotFoundException $e) {
    // return 404 server error
    $app->response()->status(404);
  } catch (Exception $e) {
    $app->response()->status(400);
    $app->response()->header('X-Status-Reason', $e->getMessage());
  }
});

// Handle POST requests to /articles
$app->post('/articles', 'authenticate', function () use ($app) {
  try {
    // get and decode JSON request body
    $request = $app->request();
    $body = $request->getBody();
    $input = json_decode($body);

    // store article record
    $article = R::dispense('articles');
    $article->title = (string)$input->title;
    $article->url = (string)$input->url;
    $article->date = (string)$input->date;
    $id = R::store($article);

    // return JSON-encoded response body
    $app->response()->header('Content-Type', 'application/json');
    echo json_encode(R::exportAll($article));
  } catch (Exception $e) {
    $app->response()->status(400);
    $app->response()->header('X-Status-Reason', $e->getMessage());
  }
});

// Handle PUT requests (Update/Modify) to /articles/:id
$app->put('/articles/:id', 'authenticate', function ($id) use ($app) {
  try {
    // get and decode JSON request body
    $request = $app->request();
    $body = $request->getBody();
    $input = json_decode($body);

    // query database for single article
    $article = R::findOne('articles', 'id=?', array($id));

    // store modified article
    // return JSON-encoded response body
    if ($article) {
      $article->title = (string)$input->title;
      $article->url = (string)$input->url;
      $article->date = (string)$input->date;
      R::store($article);
      $app->response()->header('Content-Type', 'application/json');
      echo json_encode(R::exportAll($article));
    } else {
      throw new ResourceNotFoundException();
    }
  } catch (ResourceNotFoundException $e) {
    $app->response()->status(404);
  } catch (Exception $e) {
    $app->response()->status(400);
    $app->response()->header('X-Status-Reason', $e->getMessage());
  }
});

// Handle DELETE requests to /articles/:id
$app->delete('/articles/:id', 'authenticate', function ($id) use ($app) {
  try {
    // query database for article
    $request = $app->request();
    $article = R::findOne('articles', 'id=?', array($id));

    // delete article
    if ($article) {
      R::trash($article);
      $app->response()->status(204);
    } else {
      throw new ResourceNotFoundException();
    }
  } catch (ResourceNotFoundException $e) {
    $app->response()->status(404);
  } catch (Exception $e) {
    $app->response()->status(400);
    $app->response()->header('X-Status-Reason', $e->getMessage());
  }
});

// run
$app->run();
