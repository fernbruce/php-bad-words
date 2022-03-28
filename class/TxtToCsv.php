<?php
/**
 * Class to convert fixed width files into CSV format
 * Allows to set fields, separator, and end-of-line character
 *
 * @author Kevin Waterson
 * @url http://phpro.org
 * @version $Id$
 *
 */
class fixed2CSV extends SplFileObject
{
    /**
     *
     * Constructor, duh, calls the parent constructor
     *
     * @access       public
     * @param    string  The full path to the file to be converted
     *
     */
    public function __construct ( $filename )
    {
        parent :: __construct ( $filename );
    }

    /*
    * Settor, is called when trying to assign a value to non-existing property
    *
    * @access    public
    * @param    string    $name    The name of the property to set
    * @param    mixed    $value    The value of the property
    * @throw    Excption if property is not able to be set
    *
    */
    public function __set ( $name , $value )
    {
        switch( $name )
        {
            case 'eol' :
            case 'fields' :
            case 'separator' :
                $this -> $name = $value ;
                break;

            default:
                throw new Exception ( "Unable to set $name " );
        }
    }

    /**
     *
     * Gettor This is called when trying to access a non-existing property
     *
     * @access    public
     * @param    string    $name    The name of the property
     * @throw    Exception if proplerty cannot be set
     * @return    string
     *
     */
    public function __get ( $name )
    {
        switch( $name )
        {
            case 'eol' :
                return " " ;

            case 'fields' :
                return array();

            case 'separator' :
                return ',' ;

            default:
                throw new Exception ( " $name cannot be set" );
        }
    }

    /**
     *
     * Over ride the parent current method and convert the lines
     *
     * @access    public
     * @return    string    The line as a CSV representation of the fixed width line, false otherwise
     *
     */
    public function current ()
    {
        if( parent :: current () )
        {
            $csv = '' ;
            $fields = new cachingIterator ( new ArrayIterator ( $this -> fields ) );
            foreach( $fields as $f )
            {
                $csv .= trim ( substr ( parent :: current (), $fields -> key (), $fields -> current ()  ) );
                $csv .= $fields -> hasNext () ? $this -> separator : $this -> eol ;
            }
            return $csv ;
        }
        return false ;
    }
} // end of class
?>