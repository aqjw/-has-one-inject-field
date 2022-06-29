<?php

namespace Aqjw\HasOneInjectField;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Nova\Contracts\RelatableField;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\HasOne;
use Laravel\Nova\Fields\ResourceRelationshipGuesser;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;
use Laravel\Nova\Rules\Relatable;

class HasOneInjectField extends Field implements RelatableField
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'has-one-inject-field';

    /**
     * The field data
     *
     * @var array
     */
    public $data = null;

    /**
     * The displayable singular label of the relation.
     *
     * @var string
     */
    public $compatibleWithBelongsTo = false;

    /**
     * Create a new panel instance.
     *
     * @param  string  $name
     * @param  (\Closure():array|iterable)|array  $fields
     * @return void
     */
    public function __construct($name, $attribute = null, $resource = null)
    {
        $this->data = HasOne::make($name, $attribute, $resource);
        $this->attribute = $attribute ?? str_replace(' ', '_', Str::lower($name));
        $this->name = $this->data->name;

        $resource = $resource ?? ResourceRelationshipGuesser::guessResource($name);
        $this->resourceClass = $resource;
        $this->resourceName = $resource::uriKey();
        $this->hasOneRelationship = $this->attribute = $attribute ?? ResourceRelationshipGuesser::guessRelation($name);
    }

    /**
     * Get the relationship name.
     *
     * @return string
     */
    public function relationshipName()
    {
        return $this->data->relationshipName();
    }

    /**
     * Get the relationship type.
     *
     * @return string
     */
    public function relationshipType()
    {
        return $this->data->relationshipType();
    }

    public function compatibleWithBelongsTo()
    {
        $this->compatibleWithBelongsTo = true;

        return $this;
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  string  $requestAttribute
     * @param  object  $model
     * @param  string  $attribute
     * @return mixed
     */
    protected function fillAttribute(NovaRequest $request, $requestAttribute, $model, $attribute)
    {
        $target = $this->data;
        $resourceClass = $target->resourceClass;
        $relation = $model->loadMissing($target->hasOneRelationship)->getRelation($target->hasOneRelationship) ?? $resourceClass::newModel();
        $editMode = $relation->exists === false ? 'create' : 'update';

        // get values
        $raw_values = json_decode($request->input($requestAttribute), true);
        $values = collect($raw_values)->filter();

        // do nothing for create mode if the target is nullable and values empty
       if ($target->nullable && $values->isEmpty() && $editMode === 'create') {
            return null;
        }

        $resource = new $resourceClass($relation);
        $fields = $resource->availableFields($request);

        // get rules
        $rules = $fields->mapWithKeys(function ($field) use ($request, $editMode) {
            $rules = $editMode === 'create'
                    ? $field->getUpdateRules($request)
                    : $field->getCreationRules($request);
            return $rules;

            // TODO: rethink how to solve the relatable rule
            // $rules = array_filter($rules[$field->attribute], function ($rule) {
            //     return ! $rule instanceof Relatable;
            // });
            // return [$field->attribute => $rules];
        })->all();

        // validate values
        $validator = validator()->make($values->all(), $rules);
        $errors = collect($validator->errors()->messages())
            ->mapWithKeys(function($value, $key) use ($attribute) {
                return ["{$attribute}.{$key}" => $value];
            });

        if ($errors->count()) {
          throw ValidationException::withMessages($errors->all());
        }

        // populate values into fields
        $fields->each(function ($field) use ($values, $relation) {
            if (isset($values[$field->attribute])) {
                $value = $values[$field->attribute];
                $relation->{$field->attribute} = $this->isNullValue($value) ? null : $value;
            }
        });

        if ($editMode === 'create') {
            if ($this->compatibleWithBelongsTo) {
                $relation->save();
                $model->{$model->{$target->hasOneRelationship}()->getForeignKeyName()} = $relation->getKey();
                $model->save();
            } else {
                $model->{$target->hasOneRelationship}()->save($relation);
            }

            Nova::usingActionEvent(function ($actionEvent) use ($request, $relation) {
                $actionEvent->forResourceCreate(Nova::user($request), $relation)->save();
            });
        } else {
            Nova::usingActionEvent(function ($actionEvent) use ($request, $relation) {
                $actionEvent->forResourceUpdate(Nova::user($request), $relation)->save();
            });

            $relation->save();
        }

        $model->setRelation($target->hasOneRelationship, $relation);

        return function () use ($fields) {
            $fields->filter(function ($callback) {
                return is_callable($callback);
            })->each->__invoke();
        };
    }

    /**
     * Prepare the panel for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $this->data->resolve($this->resource);

        return array_merge(parent::jsonSerialize(), [
            'data' => $this->data,
        ]);
    }

    /**
     * Handle dynamic method calls into the resource.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->data->$method(...$parameters);
    }

    /**
     * Dynamically retrieve attributes on the resource.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        if (isset($this->data->{$key})) {
            return $this->data->{$key};
        }

        return null;
    }
}
