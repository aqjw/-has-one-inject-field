<template>
  <div
    class="flex flex-col md:flex-row -mx-6 px-6 py-2 md:py-0 space-y-2 md:space-y-0"
    :class="{ 'border-t border-gray-100 dark:border-gray-700': index !== 0 }"
    :dusk="field.attribute"
  >
    <div class="md:w-1/4 md:py-3">
      <slot>
        <h4 class="font-bold md:font-normal">
          <span>{{ label }}</span>
        </h4>
      </slot>
    </div>
    <div class="md:w-3/4 md:py-3 break-all lg:break-words">
      <KeepAlive>
        <component
            :is="field.data.component"
            :field="field.data"
            :resource="resource"
            :resource-id="resourceId"
            :resource-name="resourceName"
            @actionExecuted="actionExecuted"
        />
      </KeepAlive>
    </div>
  </div>
</template>

<script>
  import BehavesAsPanel from '@/mixins/BehavesAsPanel';
  import HasOneField from '@/fields/Detail/HasOneField';

  export default {
    mixins: [BehavesAsPanel],
    props: ['index', 'resource', 'resourceName', 'resourceId', 'field', 'fieldName'],
    components: {
      HasOneField,
    },
    computed: {
      label() {
        return this.field.data.fieldName || this.field.data.name
      },
    },
  };
</script>
