<?php
namespace App\Repositories;


use App\Traits\AppliesFilters;
use Illuminate\Support\Str;

class Repository implements IRepository
{
    use AppliesFilters;
    protected $getModel;

    public function __construct(string $model)
    {
        $modelObj = 'App\\Models\\'.$model;
        $this->getModel = new $modelObj();
    }

    public static function getRepository(string $model) :IRepository{
        $modelObj = 'App\\Models\\'.$model;
        if (class_exists('App\\Repositories\\'.$model.'Repository')) {
            $repoClass = 'App\\Repositories\\'.$model.'Repository';
            $childRepository = new $repoClass($model);
            return $childRepository;
        }
        else {
            $repository = new Repository($model);

            return $repository;
        }
    }

    public function getModel() :object
    {
        return $this->getModel;
    }

    public function getAll(array $relatedObjects = []) :object
    {
        return $this->getModel->with($relatedObjects);
    }

    public function getById(int $id ,array $relatedObjects = [] ,array $relatedObjectsCount = [])  :?object
    {
        return $this->getModel->with($relatedObjects)->withCount($relatedObjectsCount)->find($id);
    }

    public function getByIdAndLock(int $id ,array $relatedObjects = [])  :?object
    {
        return $this->getModel->with($relatedObjects)->lockForUpdate()->find($id);
    }

    public function getByKey(string $key ,string $value ,array $relatedObjects = [])  :object
    {
        return $this->getModel->with($relatedObjects)->where($key,$value);
    }

    public function getByKeyValues(array $keyValues ,array $relatedObjects = [])  :object
    {
        return $this->getModel->with($relatedObjects)->where($keyValues);
    }

    public function getBykeys(string $key ,array $values ,array $relatedObjects = [])  :object
    {
        return $this->getModel->with($relatedObjects)->whereIn($key , $values);
    }

    public function create(array $data, array $relatedObjects = []) :object
    {
        $modelData = $this->getModel->create($data);
        return $modelData->load($relatedObjects);
    }

    public function insertMany(array $data) :void
    {
        $this->getModel->insert($data);
    }

    public function update(object $modelObj,array $data, array $relatedObjects = [])  :object
    {
        $modelObj->update($data);

        return $modelObj->load($relatedObjects);
    }

    public function updateByIds(array $ids ,string $key ,array $data) :bool
    {
        $updateAc = $this->getModel->whereIn($key , $ids)->update($data);

        return $updateAc;
    }

    public function delete(array $ids) :int
    {
        $deleteAc = $this->getModel->destroy($ids);

        return $deleteAc;
    }

    public function loadRelatedObjects(object $modelObject , array $relatedObjects = []) :object
    {
        return $modelObject->load($relatedObjects);
    }

    public function forceDelete(object $modelObjects) :int
    {
        $deleteCount = 0;
        foreach ($modelObjects as $modelObj)
        {
            $modelObj->forceDelete();
            $deleteCount++;
        }
        return $deleteCount;
    }

    public function syncWithUuid(object $model ,string $relation ,array $array) :void
    {
        if (!isset($array[0]))
        {
            foreach ($array as $key => $value)
            {
                $array[$key]['id'] = Str::uuid()->toString();
            }
        }
        else{
            $new_array = [];
            foreach ($array as $key => $value)
            {
                $new_array[$value]['id'] = Str::uuid()->toString();
            }
            $array = $new_array;
        }

        $model->$relation()->sync($array);

    }

    public function getByFilter(?array $filters = null ,array $relatedObjects = [] ,array $relatedObjectsCount = []) :object
    {
        $currModel = $this->getModel;
        $tableName = $currModel->getTable();
        $modelData = $currModel->with($relatedObjects)
            ->withCount($relatedObjectsCount)
            ->when(!is_null($filters) && isset($filters[$tableName]) , fn ($obj) =>
            self::appliesFilters($filters[$tableName] , $obj , $tableName
                , $filters[$tableName]['operator'])
            );

        foreach ($this->getModel->relationFilters as $relationFilter)
        {
            if($relationFilter['type'] == 'one')
            {
                $modelData->when(!is_null($filters) && isset($filters[$relationFilter['relation_name']]) , fn ($q) =>

                $q->whereHas($relationFilter['relation_name'] , fn ($object) =>

                self::appliesFilters($filters[$relationFilter['relation_name']] , $object , $relationFilter['table_name']
                    , $filters[$relationFilter['relation_name']]['operator'])
                )
                );
            } elseif ($relationFilter['type'] == 'many') {

                $modelData->when($filters != '' && isset($filters[$relationFilter['relation_name']]) , function ($q) use ($filters , $relationFilter) {

                    $operator = $filters[$relationFilter['relation_name']]['operator'];
                    foreach ($filters[$relationFilter['relation_name']] as $key => $value)
                    {
                        $filters[$relationFilter['relation_name']][$relationFilter['table_name'].'.'.$key] = $filters[$relationFilter['relation_name']][$key];
                        unset($filters[$relationFilter['relation_name']][$key]);
                    }

                    $q->whereHas($relationFilter['relation_name'] , fn ($object) =>
                    self::appliesFilters($filters[$relationFilter['relation_name']] , $object , $relationFilter['table_name'], $operator)
                    );
                });
            }

        }

        return $modelData;
    }
}
