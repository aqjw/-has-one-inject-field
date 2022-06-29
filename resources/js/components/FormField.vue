<template>
  <div
    class="field-wrapper flex flex-col border-b md:flex-row"
    :class="{ 'border-gray-100 dark:border-gray-700': index !== 0 }"
    :index="index"
    :dusk="field.attribute"
  >
    <div class="px-6 md:px-8 mt-2 md:mt-0 w-full md:w-1/5 md:py-5">
      <h4 class="font-bold md:font-normal">
        <span>{{ label }}</span>
      </h4>
    </div>
    <div class="mt-1 md:mt-0 pb-5 px-6 md:px-8 w-full md:w-3/5 md:py-5">
      <KeepAlive v-if="!field.data.from">
        <component
          :is="field.data.component"
          :errors="errors"
          :field="field.data"
          :form-unique-id="formUniqueId"
          :related-resource-id="relatedResourceId"
          :related-resource-name="relatedResourceName"
          :resource-id="resourceId"
          :resource-name="resourceName"
          :show-help-text="field.data.helpText != null"
          :shown-via-new-relation-modal="shownViaNewRelationModal"
          :via-relationship="viaRelationship"
          :via-resource="viaResource"
          :via-resource-id="viaResourceId"
          @field-changed="$emit('field-changed')"
          @file-deleted="$emit('update-last-retrieved-at-timestamp')"
          @file-upload-started="$emit('file-upload-started')"
          @file-upload-finished="$emit('file-upload-finished')"
        />
      </KeepAlive>

      <KeepAlive v-if="field.data.from">
        <component
          :is="field.data.component"
          :errors="errors"
          :resource-id="field.data.hasOneId"
          :resource-name="field.data.resourceName"
          :field="field.data"
          :via-resource="field.data.from.viaResource"
          :via-resource-id="field.data.from.viaResourceId"
          :via-relationship="field.data.from.viaRelationship"
          :form-unique-id="relationFormUniqueId"
          @field-changed="$emit('field-changed')"
          @file-deleted="$emit('update-last-retrieved-at-timestamp')"
          @file-upload-started="$emit('file-upload-started')"
          @file-upload-finished="$emit('file-upload-finished')"
          :show-help-text="field.data.helpText != null"
        />
      </KeepAlive>
    </div>
  </div>  
</template>

<script>
  import HasOneField from '@/fields/Form/HasOneField';
  import FormField from '@/mixins/FormField';
  import tap from 'lodash/tap'

  export default {
    props: [
      'index', 'resourceName',
      'resourceId', 'field', 'fieldName',
      'viaResourceId', 'errors',
    ],
    mixins: [FormField],
    components: {
      HasOneField,
    },
    computed: {
      label() {
        return this.field.data.fieldName || this.field.data.name
      },
    },
    methods: {
      fill(formData) {
        return tap(new FormData(), _formData => {
          this.field.data.fill(_formData)

          const data = {};
          _formData.forEach((value, key) => {
            key = key.match(new RegExp(`${this.field.attribute}\\[(.*)\\]`))[1]
            data[key] = value
          });

          formData.append(this.field.attribute_key, JSON.stringify(data))
        })
      },
    }
  };
</script>
