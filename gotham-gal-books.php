<?php
/**
 * Plugin Name: Gotham Gal Books
 * Description: Shortcode [gothamgal-books] for http://gothamgal.com/books-of-the-moment/
 * Author: Mike Schinkel
 * Author URI: http://about.me/mikeschinkel
 * License: GPLv2
 * Version: 1.0
 * @see: http://avc.com/2014/04/a-wordpress-plugin-for-a-books-list/
 */

class Gotham_Gal_Books {
  static $instance;
  /**
   *
   */
  static function on_load() {
    self::$instance = new Gotham_Gal_Books();
    add_action( 'init', array( self::$instance, '_init' ) );
  }

  /**
   *
   */
  function _init() {
    add_action( 'save_post', array(  $this, '_save_post' ), 10, 2 );
    add_action( 'edit_form_after_title', array( $this, '_edit_form_after_title' ), 10, 2 );

    add_shortcode( 'gothamgal-books', array( $this, 'books_shortcode' ) );

    register_post_type( Gotham_Gal_Book::POST_TYPE, array(
      'label'               => __( 'Books', 'gg-books' ),
      'description'         => __( 'Gotham Gal\'s Books of the Moment', 'gg-books' ),
      'labels'              => array(
        'name'                => _x( 'Books', 'Post Type General Name', 'gg-books' ),
        'singular_name'       => _x( 'Book', 'Post Type Singular Name', 'gg-books' ),
        'menu_name'           => __( 'Books', 'gg-books' ),
        'parent_item_colon'   => __( 'Parent Book:', 'gg-books' ),
        'all_items'           => __( 'All Books', 'gg-books' ),
        'view_item'           => __( 'View Book', 'gg-books' ),
        'add_new_item'        => __( 'Add New Book', 'gg-books' ),
        'add_new'             => __( 'New Book', 'gg-books' ),
        'edit_item'           => __( 'Edit Book', 'gg-books' ),
        'update_item'         => __( 'Update Book', 'gg-books' ),
        'search_items'        => __( 'Search books', 'gg-books' ),
        'not_found'           => __( 'No books found', 'gg-books' ),
        'not_found_in_trash'  => __( 'No books found in Trash', 'gg-books' ),
      ),
      'supports'            => array( 'title', 'editor' ),
      'taxonomies'          => array(),
      'hierarchical'        => true,
      'public'              => true,
      'show_ui'             => true,
      'show_in_menu'        => true,
      'show_in_nav_menus'   => true,
      'show_in_admin_bar'   => true,
      'menu_position'       => 5,
      'menu_icon'           => '',
      'can_export'          => true,
      'has_archive'         => true,
      'exclude_from_search' => false,
      'publicly_queryable'  => true,
      'query_var'           => Gotham_Gal_Book::POST_TYPE,
      'rewrite'             => array(
        'slug'                => 'books',
        'with_front'          => true,
        'pages'               => true,
        'feeds'               => true,
      ),
      'capability_type'     => 'page',
      'form'                => 'after-title',
    ));

    register_post_status( 'archive', array(
      'label'       => _x( 'Archived', 'gg-books' ),
      'public'      => true,
      'label_count' => _n_noop( 'Archived <span class="count">(%s)</span>', 'Archived <span class="count">(%s)</span>' ),
    ));

  }
/**
 * Displays the main edit form after the title and above the body editor.
 *
 * @param WP_Post $post
 */
function _edit_form_after_title( $post ) {
  if( Gotham_Gal_Book::POST_TYPE == $post->post_type ) {
    $this->the_form( $post );
  }
}

  /**
   * Display the Form for entering an ASIN
   *
   * @param WP_Post $post
   */
  function the_form( $post ) {
    $book = new Gotham_Gal_Book( $post ); ?>
<style>
#gg-books-form {
  margin: 1em ;
}
#gg-books-form label {
  display: inline-block;
  width: 7em;
}
#gg-books-form input {
  margin:1em 0 1em 0;
  display: inline-block;
  height:2em;
}
</style>
<script type="text/javascript">
jQuery(function($) {
  var archiveText = "<?php _e( 'Archive', 'gg-books' ); ?>";
  var post_status = $('#post_status');
  var hidden_post_status = $("#hidden_post_status");
  var post_status_display = $("#post-status-display");
  var curval = hidden_post_status.val();
  var curtext = 'archive'==curval?archiveText:post_status.find("option[value='"+curval+"']").text();
  post_status.append($("<option value='archive'>"+archiveText+"</option>"));
  if (curval!=hidden_post_status.val())
    hidden_post_status.val(curval);
  if (curtext!=post_status_display.text())
    post_status_display.text(curtext);
});
</script>
<div id="gg-books-form">
  <label for="gg-asin"><?php echo __( "Amazon ASIN:", 'gg-books' ); ?></label>
  <input type="text" id="gg-asin" name="gg_asin" value="<?php $book->the_asin(); ?>" placeholder="A12345678X" />
</div>
<?php
}

  /**
   * @param int $post_id
   * @param WP_Post $post
   */
  function _save_post( $post_id, $post ) {
    if( Gotham_Gal_Book::POST_TYPE == $post->post_type ) {
      $field_name = 'gg_asin';
      $meta_key = "_{$field_name}";
      $asin = filter_input( INPUT_POST, $field_name, FILTER_SANITIZE_STRING );
      if ( empty( $asin ) ) {
        delete_post_meta( $post_id, $meta_key );
      } else {
        update_post_meta( $post_id, $meta_key, $asin );
      }
    }
  }

  /**
   * @param array $args
   * @param string $content
   * @return string
   */
  function books_shortcode( $args, $content ) {
    //$args = wp_parse_args( $args, array(
    //  'arg1' => 'default1',
    //));
    $books = new Gotham_Gal_Book_Collection();
    ob_start();
    /**
     * @var Gotham_Gal_Book $book
     */
    foreach( $books->collection() as $book ):
      ?><p><?php $book->the_cover_image_link(); ?><?php $book->the_title_link(); ?> &ndash; <?php $book->the_content();?></p><?php
    endforeach;
    $books_html = ob_get_clean();
    $html ="<div class=\"books-page-container\">{$books_html}<div class=\"clearfix\"></div></div>";
    return $html;
  }

}
Gotham_Gal_Books::on_load();


/**
 * Class Gotham_Gal_Book
 *
 * @method null the_asin_url()
 * @method null the_cover_image_link()
 * @method null the_title()
 * @method null the_title_link()
 * @method null the_content()
 *
 * @property string asin_url
 * @property string cover_image
 * @property string cover_image_url
 * @property string the_cover_image_link
 */
class Gotham_Gal_Book {
  const POST_TYPE = 'gg_book';

  /**
   * @var WP_Post
   */
  private $_post;

  function __construct( $post ) {
    $this->_post = $post;
  }

  function ID() {
    return $this->_post->ID;
  }

  function the_asin() {
    echo esc_attr( $this->asin() );
  }

  function asin() {
    return sanitize_key( get_post_meta( $this->ID(), '_gg_asin', true ) );
  }

  function asin_url() {
    return "http://www.amazon.com/exec/obidos/ASIN/{$this->asin()}/gothamgal-20";
  }

  function title() {
    return get_the_title( $this->ID() );
  }

  function title_link() {
    return "<a target=\"_blank\" href=\"{$this->asin_url}\">{$this->title}</a>";
  }

  function content() {
    return $this->_post->post_content;
  }

  function cover_alt() {
    return __( sprintf( "Cover of %s", $this->title() ), 'gg_books' );
  }

  function cover_image_html() {
    return "<img alt=\"{$this->cover_alt}\" src=\"{$this->cover_image_url}\">";
  }

  /**
   * @see: http://aaugh.com/imageabuse.html
   * @see: http://www.ancestryinsider.org/2009/06/book-cover-images-from-amazoncom.html
   */
  function cover_image_url() {
    return "http://images.amazon.com/images/P/{$this->asin()}.01._PC20,0,0,20_.jpg";
  }

  function cover_image_link() {
    return "<a target=\"_blank\" href=\"{$this->asin_url}\" >{$this->cover_image_html}</a>";
  }

  /**
   * @param string $method_name
   * @param array $args
   * @return mixed|null
   */
  function __call( $method_name, $args ) {
    $result = null;
    if ( method_exists( $this, $the_method = preg_replace( '#^the_(.*)$#', '$1', $method_name ) ) ) {
      echo $result = call_user_func( array( $this, $the_method ) );
    } else {
      $message = __( 'Object of class %s does not contain a method named %s().', 'gg_books' );
      trigger_error( sprintf( $message, get_class( $this ), $method_name ), E_USER_WARNING );
      $result = null;
    }
    return $result;
  }

  /**
   * @param string $property_name
   * @return mixed|null
   */
  function __get( $property_name ) {
    if ( method_exists( $this, $property_name ) ) {
      $value = call_user_func( array( $this, $property_name ) );
    } else {
      $message = __( 'Object of class %s does not contain a property or method named %s().', 'gg_books' );
      trigger_error( sprintf( $message, get_class( $this ), $property_name ), E_USER_WARNING );
      $value = null;
    }
    return $value;
  }

}

/**
 * Class Gotham_Gal_Book_Collection
 */
class Gotham_Gal_Book_Collection {
  /**
   * @var WP_Query
   */
  private $_query;

  /**
   * @var array
   */
  private $_collection;

  /**
   * @param array $args
   */
  function __construct( $args = array() ) {
    $args = wp_parse_args( $args, array(
      'posts_per_page' => -1,
      'post_status' => 'publish',
    ));
    $args['post_type'] = Gotham_Gal_Book::POST_TYPE;

    $this->_query = new WP_Query( $args );
  }

  /**
   * @return array
   */
  function collection() {
    if ( ! isset( $this->_collection ) ) {
      $this->_collection = array();
      foreach( $this->_query->posts as $post ) {
        $this->_collection[$post->ID] = new Gotham_Gal_Book( $post );
      }
    }
    return $this->_collection;
  }

}
