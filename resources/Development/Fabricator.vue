<template>
    <div class="w-full h-full border border-gray-100 flex flex-wrap text-white">
        <KeepAlive>
            <div  class="w-full h-full border border-gray-100 flex flex-wrap text-white">
                <div class="w-3/4 border border-gray-200">
                    <draggable 
                        v-model="contents"
                        group="components"
                        class="p-8 border border-red-400 flex flex-wrap"
                        item-key="id"
                    >
                        <template #item="{element}">
                            <component 
                                :is="element.name" 
                                :class="element.width"
                            ></component>
                        </template>
                    </draggable>
                </div>
                <div class="w-1/4 border border-gray-300">

                    <draggable 
                        :list="components"
                        class="p-8 border border-red-400" 
                        :group="{ name: 'components', pull: 'clone', put: false }"
                        item-key="id"
                    >
                        <template #item="{ element }">
                            <div>
                                <div class="flex flex-col">
                                    <div class="font-bold">{{ element.name }}</div>
                                    <div>{{ element.description }}</div>
                                </div>
                            </div>
                        </template>
                    </draggable>
                </div>
            </div>
        </KeepAlive>
    </div>
</template>

<script>
/**
 * Fabricator will be a plugin focused on dynamic page creation. 
 *   It'll generate a vue component to be used for new routes.
 *   It'll have a drag and drop GUI for building out the component, filling static information, and setting the route path.
 *      Meaning, I want it to have a set of Vue components it can have a preview of, and then I can drag and drop them into the page.
 *   I want to be able to set properties on the component, predefined data portions, computed values etc, ...
 */
import draggable from 'vuedraggable'

export default {
    components: {
        draggable,
    },
    data() {
        return {
            draggingComponent: false,
            draggingContent: false,
            components: Spork.fabrications,
            contents: [],
        }
    }
}
</script>
