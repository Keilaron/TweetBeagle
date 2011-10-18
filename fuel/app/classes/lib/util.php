<?php


class Lib_Util
{

}

class Lib_Arr
{
  /**
   * Gathers all the ID properties of the elements of $array in a list.
   * @param array $array The target array to be searched.
   * @return array A list of extracted IDs
   */
  public static function extractIds(array $array)
  {
    $peek = reset($array);

    if (empty($array))
      throw new InvalidArgumentException ('Empty array supplied to the function.');

    if (is_array($peek))
      ;
    else if ($peek instanceof stdClass)
      $peek = (array)$peek;
    else
      throw new InvalidArgumentException ('Elements of $array must be either arrays or of type `stdClass`. Other types are not supported.');

    if (!is_numeric($peek['id']))
    {
      throw new InvalidArgumentException ('The passed argument does not contain
        elements with an `id` property');
    }

    $ids = array();

    foreach ($array as $element)
    {
      if (is_object($element))
        $ids[] = $element->id;
      else
        $ids[] = $element['id'];
    }

    return $ids;
  }
}
