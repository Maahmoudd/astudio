<?php
/**
 * Created by PhpStorm.
 * User: Maahmoudd
 * Date: 18/3/2025
 * Time: 17:3 AM
 */

namespace App\Traits;


use Illuminate\Support\Facades\Schema;

trait AppliesFilters
{
    public static function appliesFilters($filters,$object,$tableName , $operator){

        if($filters) {
            $object->where(function($query) use ($filters,$tableName, $operator){
                $index = 0;
                $concatKeys = "CONCAT(";
                foreach ($filters as $key => $filter) {
                    if(is_null($filter) || empty($filter))
                        continue;

                    $keyArr = explode('.',$key);
                    $originalKey = sizeof($keyArr) == 2 ? $keyArr[1] : $keyArr[0];

                    if(Schema::hasColumn($tableName, $originalKey)) {

                        if(is_array($filter))
                        {
                            if(isset($filter['from']) && isset($filter['to']))
                            {
                                if ($index === 0)
                                    $query->whereBetween($key, [$filter['from'] , $filter['to']]);
                                else {
                                    if ($operator == 'and')
                                        $query->whereBetween($key, [$filter['from'] , $filter['to']]);
                                    elseif ($operator == 'or')
                                        $query->orWhereBetween($key, [$filter['from'] , $filter['to']]);
                                }
                            } elseif(isset($filter['operator']) && isset($filter['value'])) {
                                $insideOperator = $filter['operator'];
                                if ($index === 0) {
                                    if(in_array($insideOperator , ['whereIn' , 'whereNotIn']))
                                        $query->$insideOperator($key, $filter['value']);
                                    else
                                        $query->where($key , $insideOperator , $filter['value']);
                                }
                                else {
                                    if ($operator == 'or')
                                        $insideOperator = $operator.ucfirst($insideOperator);

                                    if(in_array($insideOperator , ['whereIn' , 'whereNotIn']))
                                        $query->$insideOperator($key, $filter['value']);
                                    else
                                        $query->where($key , $insideOperator , $filter['value']);
                                }
                            }else {
                                if ($index === 0)
                                    $query->whereIn($key, $filter);
                                else {
                                    if ($operator == 'and')
                                        $query->whereIn($key, $filter);
                                    elseif ($operator == 'or')
                                        $query->orWhereIn($key, $filter);
                                }
                            }
                        } else {

                            if ($index === 0)
                                $query->where($key, 'like', '%' . $filter . '%');
                            else {
                                if ($operator == 'and')
                                    $query->where($key, 'like', '%' . $filter . '%');
                                elseif ($operator == 'or')
                                    $query->orWhere($key, 'like', '%' . $filter . '%');
                            }
                        }

                        $concatKeys = $concatKeys . $key . " , ' ',";
                        $index = $index+1;
                    }
                }

            });
        }

        return $object;
    }
}
