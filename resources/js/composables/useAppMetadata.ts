import { ref, computed, provide, inject } from 'vue'

/**
 * Application metadata interface for app information
 *
 * This interface defines the structure for application metadata including
 * title, icon, and subtitle information used throughout the application.
 *
 * @interface AppMetadata
 * @since 1.0.0
 */
export interface AppMetadata {
  /** The title/name of the application */
  title: string
  /** The icon identifier (class name, URL, etc.) */
  icon: string
  /** CSS class for icon styling */
  iconClass: string
  /** Optional subtitle or description */
  subtitle?: string
}

/** Symbol for Vue provide/inject pattern */
const APP_METADATA_KEY = Symbol('appMetadata')

/**
 * Provide app metadata context for Vue components
 *
 * This function sets up the Vue provide/inject pattern for sharing
 * application metadata across component hierarchies. It creates a
 * reactive metadata object that can be updated and accessed by child components.
 *
 * The provide/inject pattern allows parent components to provide data
 * that can be accessed by any descendant component in the component tree,
 * regardless of how deep the nesting is.
 *
 * @returns {Object} Object containing reactive metadata and update function
 * @returns {ComputedRef<AppMetadata>} returns.metadata Reactive metadata object
 * @returns {Function} returns.updateMetadata Function to update metadata
 *
 * @example
 * ```typescript
 * // In parent component
 * const { metadata, updateMetadata } = provideAppMetadata()
 * 
 * // Update metadata
 * updateMetadata({ title: 'New App Title', icon: 'fas fa-star' })
 * ```
 */
export function provideAppMetadata() {
  const metadata = ref<AppMetadata>({
    title: 'Application',
    icon: 'fas fa-puzzle-piece',
    iconClass: 'gray-icon'
  })

  const updateMetadata = (newMetadata: Partial<AppMetadata>) => {
    // Merge new metadata with existing metadata, ensuring required fields are present
    const currentValue = metadata.value
    if (currentValue) {
      metadata.value = {
        title: newMetadata.title || currentValue.title,
        icon: newMetadata.icon || currentValue.icon,
        iconClass: newMetadata.iconClass || currentValue.iconClass,
        subtitle: newMetadata.subtitle !== undefined ? newMetadata.subtitle : currentValue.subtitle
      }
    }
  }

  const context = {
    metadata: computed(() => metadata.value),
    updateMetadata
  }

  // Provide the context to descendant components
  provide(APP_METADATA_KEY, context)

  return context
}

/**
 * Use app metadata in Vue components
 *
 * This function provides access to application metadata through Vue's
 * inject system. It returns the current metadata and a function to
 * update it. If used outside of a component that provides metadata,
 * it returns a fallback implementation.
 *
 * The inject system allows any descendant component to access the
 * metadata provided by a parent component, enabling cross-component
 * communication without prop drilling.
 *
 * @returns {Object} Object containing reactive metadata and update function
 * @returns {ComputedRef<AppMetadata>} returns.metadata Reactive metadata object
 * @returns {Function} returns.updateMetadata Function to update metadata
 *
 * @example
 * ```typescript
 * // In child component
 * const { metadata, updateMetadata } = useAppMetadata()
 * 
 * // Access metadata
 * console.log(metadata.value.title)
 * 
 * // Update metadata
 * updateMetadata({ subtitle: 'New subtitle' })
 * ```
 */
export function useAppMetadata() {
  // Try to inject the metadata context from a parent component
  const context = inject(APP_METADATA_KEY)
  
  if (context) {
    // Return the injected context if available
    return context
  }
  
  // Fallback implementation for when used outside of app context
  const metadata = ref<AppMetadata>({
    title: 'Application',
    icon: 'fas fa-puzzle-piece',
    iconClass: 'gray-icon'
  })

  return {
    metadata: computed(() => metadata.value),
    updateMetadata: (newMetadata: Partial<AppMetadata>) => {
      // Merge new metadata with existing metadata, ensuring required fields are present
      const currentValue = metadata.value
      if (currentValue) {
        metadata.value = {
          title: newMetadata.title || currentValue.title,
          icon: newMetadata.icon || currentValue.icon,
          iconClass: newMetadata.iconClass || currentValue.iconClass,
          subtitle: newMetadata.subtitle !== undefined ? newMetadata.subtitle : currentValue.subtitle
        }
      }
    }
  }
}