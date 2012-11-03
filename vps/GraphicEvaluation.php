<?
class GraphicEvaluation {
  # config
  protected $SIMILAR_TRESHOLD = 50;
  protected $COLORS = array(
    0x000000,
    0xff0000,
    0x008000,
    0xffa500,
    0xffff00,
    0x82b82c,
    0x0099ff,
    0xff00ff,
    0x8000ff,
    0x99fe00
  );
  # img variables
  protected $img;
  protected $width;
  protected $height;
  # evaluation variables
  protected $colors_used;
  protected $pixels_drawn;
  # internal object state vars
  private $shapes;
  private $color_shapes;
  
  public function __construct( $file ) {
    list( $this->width, $this->height, $_ ) = getimagesize( $file );
    $this->img = imagecreatefrompng( $file );

    $this->colors_used = array();
    $this->pixels_drawn = 0;
    $this->shapes = array();
    $this->color_shapes = array();

    error_reporting( E_ALL & ~E_NOTICE );
  }

  private function r( $rgb ) {
    return ($rgb >> 16) & 0xFF;
  }
  
  private function g( $rgb ) {
    return ($rgb >> 8) & 0xFF;
  }
  
  private function b( $rgb ) {
    return $rgb & 0xFF;
  }

  private function is_white( $rgb ) {
    return $this->r( $rgb ) == 0xFF && $this->g( $rgb ) == 0xFF && $this->b( $rgb ) == 0xFF;
  }

  private function draw_color( $color ) {
    if( $this->is_white( $color ) )
      return $color;
    if( in_array( $color, $this->COLORS ) )
      return $color;
    foreach( $this->COLORS as $existing )
      if( $this->similar_colors( $color, $existing ) )
        return $existing;
    return 0xFFFFFF;
  }

  private function similar_colors( $c1, $c2 ) {
    if( abs( $this->r( $c1 ) - $this->r( $c2 ) ) < $this->SIMILAR_TRESHOLD && abs( $this->g( $c1 ) - $this->g( $c2 ) ) < $this->SIMILAR_TRESHOLD && abs( $this->b( $c1 ) - $this->b( $c2 ) ) < $this->SIMILAR_TRESHOLD )
      return true;
    return false;
  }

  private function merge_shapes( $from, $to ) {
    #echo "\n\n\nMERGE: from $from to $to\n\n\n";
    #print_r( $this->shapes );
    #print_r( $this->color_shapes );
    foreach( $this->shapes[$from] as $pair ) {
      $this->color_shapes[$pair[0]][$pair[1]] = $to;
      $this->shapes[$to][] = $pair;
    }
    $this->shapes[$from] = -1;
  }

  private function similar_shapes( $shape1, $shape2 ) {
    if( sizeof( $this->shapes[$shape1] ) != sizeof( $this->shapes[$shape2] ) )
      return false;
    $this->move_to_zero( $shape1 );
    $this->move_to_zero( $shape2 );
    foreach( $this->shapes[$shape1] as $key => $pair1 ) {
      $pair2 = $this->shapes[$shape2][$key];
      if( $pair1[0] != $pair2[0] || $pair1[1] != $pair2[1] )
        return false;
    }
    return true;
  }

  private function move_to_zero( $shape ) {
    $min_x = $min_y = 9999;
    foreach( $this->shapes[$shape] as $pair ) {
      $min_x = min( $min_x, $pair[0] );
      $min_y = min( $min_y, $pair[1] );
    }
    if( $min_x > 0 || $min_y > 0 )
      foreach( $this->shapes[$shape] as $key => $_ ) {
        $this->shapes[$shape][$key][0] -= $min_x;
        $this->shapes[$shape][$key][1] -= $min_y;
      }
  }

  private function half_percent( $score, $max ) {
    return ( $max - min( abs( $max - $score ), $max ) ) / $max;
  }

  public function evaluate() {
    for( $y = 0; $y < $this->height; $y++ )
      for( $x = 0; $x < $this->width; $x++ ) {
        $color = $this->draw_color( imagecolorat( $this->img, $x, $y ) );
        if( $this->is_white( $color ) )
          continue;
        #echo "0x".dechex( $color )." - $x x $y\n";
        $this->colors_used[(string)$color] = true;
        $this->pixels_drawn++;
        # add the current pixel to a shape if it is not part of any shape
        $shape = $this->color_shapes[$x][$y];
        if( !$shape ) {
          $shape = sizeof( $this->shapes )+1;
          $this->color_shapes[$x][$y] = $shape;
          $this->shapes[$shape] = array( array( $x, $y ) );
        }
        # check right and bottom pixel for the same color - mark each of them with a number and count differnet non-adjecent shapes
        if( $x+1 < $this->width ) {
          $right = $this->draw_color( imagecolorat( $this->img, $x+1, $y ) );
          if( $color == $right ) {
            # check if right is already assigned to a share -> then merge the two shapes as they are the same
            $right_shape = $this->color_shapes[$x+1][$y];
            if( $right_shape ) {
              if( $shape != $right_shape ) {
                #echo "x:$x-y:$y\n";
                $this->merge_shapes( $shape, $right_shape );
                $shape = $right_shape;
              }
            } else {
              $this->color_shapes[$x+1][$y] = $shape;
              $this->shapes[$shape][] = array( $x+1, $y );
            }
          }
        }
        if( $y+1 < $this->height ) {
          $bottom = $this->draw_color( imagecolorat( $this->img, $x, $y+1 ) );
          if( $color == $bottom ) {
            $this->color_shapes[$x][$y+1] = $shape;
            $this->shapes[$shape][] = array( $x, $y+1 );
          }
        }
      }

    # remove merged shapes
    foreach( $this->shapes as $key => $shape )
      if( $shape == -1 )
        unset( $this->shapes[$key] );

    # calculate number of different shapes based on pixel comparison
    $ungrouped_shapes = array_keys( $this->shapes );
    $grouped_shapes = array();
    while( $ungrouped_shapes ) {
      $shape = array_shift( $ungrouped_shapes );
      $grouped_shapes[] = array( $shape );
      $group = sizeof( $grouped_shapes ) - 1;
      foreach( $ungrouped_shapes as $key => $shape_to_compare )
        if( $this->similar_shapes( $shape, $shape_to_compare ) ) {
          $grouped_shapes[$group][] = $shape_to_compare;
          unset( $ungrouped_shapes[$key] );
        }
    }

    $total_pixels = $this->width * $this->height;
    $colors_used_score = sizeof( $this->colors_used );
    $pixels_drawn_score = round( $this->pixels_drawn / $total_pixels * 100 );

    echo "Colors used: $colors_used_score\n";
    echo "Pixels drawn: $this->pixels_drawn/$total_pixels ($pixels_drawn_score%)\n";
    echo "Total shapes: ".sizeof( $this->shapes )."\n";
    #print_r( $shapes );
    echo "Different shapes: ".sizeof( $grouped_shapes )."\n";
    #print_r( $grouped_shapes );

    $elaboration_score = array();
    // 1,2,3,4,5,6+ colors used = 33,66,100,66,33,0%
    $elaboration_score[] = $this->half_percent( $colors_used_score, 3 );
    // 50% pixels drawn = 100% score, 50->0, 50->100 decreasing to 0%
    $elaboration_score[] = $this->half_percent( $pixels_drawn_score, 50 );
    if( $this->shapes )
      $elaboration_score[] = sizeof( $grouped_shapes ) / sizeof( $this->shapes );
    else
      $elaboration_score[] = 0;
    #print_r( $elaboration_score );
    echo "Elaboration score: ".number_format( 100 * array_sum( $elaboration_score ) / sizeof( $elaboration_score ) )."%\n";
  }

}