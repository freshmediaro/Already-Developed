/// <reference types="vite/client" />

// Vue module declarations
declare module 'vue' {
  export interface App {
    [key: string]: any
  }
  export interface DefineComponent {
    [key: string]: any
  }
  export function ref<T>(value?: T): { value: T | undefined }
  export function reactive<T extends object>(target: T): T
  export function computed<T>(getter: () => T): { value: T }
  export function provide<T>(key: symbol, value: T): void
  export function inject<T>(key: symbol, defaultValue?: T): T | undefined
  export function onMounted(callback: () => void): void
  export function onUnmounted(callback: () => void): void
  export function watch<T>(source: T, callback: (newVal: T, oldVal: T) => void): void
  export function nextTick(callback?: () => void): Promise<void>
  export function createApp(rootComponent: any): App
  export function defineAsyncComponent(loader: () => Promise<any>): DefineComponent
  export * from 'vue'
  const Vue: {
    createApp: (rootComponent: any) => App
    [key: string]: any
  }
  export default Vue
}

declare module '*.vue' {
  import type { DefineComponent } from 'vue'
  const component: DefineComponent<{}, {}, unknown>
  export default component
}

// Heroicons module declarations
declare module '@heroicons/vue/24/outline' {
  import type { DefineComponent } from 'vue'
  export const GlobeAltIcon: DefineComponent
  export const SquaresPlusIcon: DefineComponent
  export const CreditCardIcon: DefineComponent
  export const TruckIcon: DefineComponent
  export const UsersIcon: DefineComponent
  export const EnvelopeIcon: DefineComponent
  export const DocumentTextIcon: DefineComponent
  export const ChevronUpIcon: DefineComponent
  export const ChevronDownIcon: DefineComponent
  export const ChevronLeftIcon: DefineComponent
  export const ChevronRightIcon: DefineComponent
  export const XMarkIcon: DefineComponent
  export const MinusIcon: DefineComponent
  export const Squares2X2Icon: DefineComponent
  export const MagnifyingGlassIcon: DefineComponent
  export const Bars3Icon: DefineComponent
  export const EllipsisVerticalIcon: DefineComponent
  export const PlusIcon: DefineComponent
  export const PencilIcon: DefineComponent
  export const TrashIcon: DefineComponent
  export const ArrowLeftIcon: DefineComponent
  export const ArrowRightIcon: DefineComponent
  export const HomeIcon: DefineComponent
  export const FolderIcon: DefineComponent
  export const DocumentIcon: DefineComponent
  export const PhotoIcon: DefineComponent
  export const PlayIcon: DefineComponent
  export const MusicalNoteIcon: DefineComponent
  export const CodeBracketIcon: DefineComponent
  export const TerminalIcon: DefineComponent
  export const PaintBrushIcon: DefineComponent
  export const ArchiveBoxIcon: DefineComponent
  export const CpuChipIcon: DefineComponent
  export const CloudArrowUpIcon: DefineComponent
  export const InboxIcon: DefineComponent
  export const CalendarIcon: DefineComponent
  export const ChatBubbleLeftRightIcon: DefineComponent
  export const CogIcon: DefineComponent
  export const BuildingStorefrontIcon: DefineComponent
  export const WalletIcon: DefineComponent
  export const BanknotesIcon: DefineComponent
  export const UserIcon: DefineComponent
  export const BellIcon: DefineComponent
  export const SunIcon: DefineComponent
  export const MoonIcon: DefineComponent
  export const ComputerDesktopIcon: DefineComponent
  export const DevicePhoneMobileIcon: DefineComponent
  export const ShieldCheckIcon: DefineComponent
  export const CubeIcon: DefineComponent
  export const CheckIcon: DefineComponent
  export const InformationCircleIcon: DefineComponent
  export const ExclamationTriangleIcon: DefineComponent
  export const EyeIcon: DefineComponent
  export const EyeSlashIcon: DefineComponent
  export const ShareIcon: DefineComponent
  export const LinkIcon: DefineComponent
  export const ClipboardIcon: DefineComponent
  export const AdjustmentsHorizontalIcon: DefineComponent
  export const StarIcon: DefineComponent
  export const HeartIcon: DefineComponent
  export const TagIcon: DefineComponent
  export const WindowIcon: DefineComponent
  export const LightBulbIcon: DefineComponent
  export const ArrowsPointingOutIcon: DefineComponent
  export const ArrowsPointingInIcon: DefineComponent
  export const ArrowPathIcon: DefineComponent
  export const CalculatorIcon: DefineComponent
  export const PuzzlePieceIcon: DefineComponent
  export const ArrowDownTrayIcon: DefineComponent
  export const ArrowUpIcon: DefineComponent
}

declare module '@heroicons/vue/24/solid' {
  import type { DefineComponent } from 'vue'
  export const CheckIcon: DefineComponent
  export const XMarkIcon: DefineComponent
  export const InformationCircleIcon: DefineComponent
  export const ExclamationTriangleIcon: DefineComponent
  export const StarIcon: DefineComponent
  export const HeartIcon: DefineComponent
}

// Headless UI module declarations
declare module '@headlessui/vue' {
  import type { DefineComponent } from 'vue'
  export const Dialog: DefineComponent
  export const DialogPanel: DefineComponent
  export const DialogTitle: DefineComponent
  export const DialogDescription: DefineComponent
  export const TransitionRoot: DefineComponent
  export const TransitionChild: DefineComponent
  export const Menu: DefineComponent
  export const MenuButton: DefineComponent
  export const MenuItems: DefineComponent
  export const MenuItem: DefineComponent
  export const Popover: DefineComponent
  export const PopoverButton: DefineComponent
  export const PopoverPanel: DefineComponent
  export const Switch: DefineComponent
  export const SwitchGroup: DefineComponent
  export const SwitchLabel: DefineComponent
  export const Tab: DefineComponent
  export const TabGroup: DefineComponent
  export const TabList: DefineComponent
  export const TabPanel: DefineComponent
  export const TabPanels: DefineComponent
  export const Disclosure: DefineComponent
  export const DisclosureButton: DefineComponent
  export const DisclosurePanel: DefineComponent
  export const Listbox: DefineComponent
  export const ListboxButton: DefineComponent
  export const ListboxOptions: DefineComponent
  export const ListboxOption: DefineComponent
  export const Combobox: DefineComponent
  export const ComboboxInput: DefineComponent
  export const ComboboxButton: DefineComponent
  export const ComboboxOptions: DefineComponent
  export const ComboboxOption: DefineComponent
}

// Inertia module declarations
declare module '@inertiajs/vue3' {
  import type { DefineComponent } from 'vue'
  export const Link: DefineComponent
  export const Head: DefineComponent
  export function createInertiaApp(config: any): Promise<void>
  export function router(): any
  export function usePage(): any
  export function useForm(data?: any): any
}

// Axios module declaration
declare module 'axios' {
  export interface AxiosRequestConfig {
    [key: string]: any
  }
  export interface AxiosResponse {
    data: any
    status: number
    statusText: string
    headers: any
    config: AxiosRequestConfig
  }
  export interface AxiosInstance {
    request(config: AxiosRequestConfig): Promise<AxiosResponse>
    get(url: string, config?: AxiosRequestConfig): Promise<AxiosResponse>
    delete(url: string, config?: AxiosRequestConfig): Promise<AxiosResponse>
    head(url: string, config?: AxiosRequestConfig): Promise<AxiosResponse>
    options(url: string, config?: AxiosRequestConfig): Promise<AxiosResponse>
    post(url: string, data?: any, config?: AxiosRequestConfig): Promise<AxiosResponse>
    put(url: string, data?: any, config?: AxiosRequestConfig): Promise<AxiosResponse>
    patch(url: string, data?: any, config?: AxiosRequestConfig): Promise<AxiosResponse>
    defaults: {
      headers: {
        common: Record<string, string>
      }
    }
  }
  const axios: AxiosInstance
  export default axios
}

// ElFinder module declarations
declare module 'elfinder' {
  export interface ElFinderOptions {
    [key: string]: any
  }
  export class ElFinder {
    constructor(element: HTMLElement, options: ElFinderOptions)
    destroy(): void
    [key: string]: any
  }
  export default ElFinder
}

declare module '@types/elfinder' {
  export * from 'elfinder'
}

// Lodash module declaration
declare module 'lodash-es' {
  export function debounce<T extends (...args: any[]) => any>(
    func: T,
    wait?: number,
    options?: { leading?: boolean; maxWait?: number; trailing?: boolean }
  ): T & { cancel(): void; flush(): ReturnType<T> }
  export function throttle<T extends (...args: any[]) => any>(
    func: T,
    wait?: number,
    options?: { leading?: boolean; trailing?: boolean }
  ): T & { cancel(): void; flush(): ReturnType<T> }
  export function clone<T>(value: T): T
  export function cloneDeep<T>(value: T): T
  export function merge<T, U>(object: T, source: U): T & U
  export function pick<T, K extends keyof T>(object: T, ...paths: K[]): Pick<T, K>
  export function omit<T, K extends keyof T>(object: T, ...paths: K[]): Omit<T, K>
  export function isEmpty(value: any): boolean
  export function isEqual(value: any, other: any): boolean
  export function uniq<T>(array: T[]): T[]
  export function uniqBy<T>(array: T[], iteratee: string | ((value: T) => any)): T[]
  export function sortBy<T>(collection: T[], iteratees: string | ((value: T) => any)): T[]
  export function groupBy<T>(collection: T[], iteratee: string | ((value: T) => any)): { [key: string]: T[] }
  export function keyBy<T>(collection: T[], iteratee: string | ((value: T) => any)): { [key: string]: T }
  export function capitalize(string: string): string
  export function camelCase(string: string): string
  export function kebabCase(string: string): string
  export function snakeCase(string: string): string
  export function startCase(string: string): string
}

// Laravel global variables
declare const route: (name: string, params?: Record<string, unknown>) => string

// Desktop application globals
interface Window {
  axios: unknown;
  desktopApp: {
    initialize: () => Promise<void>;
    on: (event: string, callback: (data: unknown) => void) => void;
    launchApp: (appId: string) => Promise<string>;
    activateWindow: (windowId: string) => void;
    switchTeam: (teamId: string) => Promise<void>;
  };
  vueDesktop: {
    launchApp: (appId: string) => Promise<string | null>;
    showNotifications: () => void;
    showWidgets: () => void;
    toggleStartMenu: () => void;
    toggleGlobalSearch: () => void;
  };
  elFinder: unknown;
}

// Inertia page props
declare interface PageProps {
  auth: {
    user: Record<string, unknown>
  }
  errors: Record<string, string>
  flash: {
    message?: string
    error?: string
  }
  currentTeam?: Record<string, unknown>
  allTeams?: Record<string, unknown>[]
  userPreferences?: Record<string, unknown>
  installedApps?: Record<string, unknown>[]
  desktopConfig?: Record<string, unknown>
}

// Environment variables
interface ImportMetaEnv {
  readonly VITE_APP_NAME: string
  readonly VITE_APP_ENV: string
  readonly VITE_APP_DEBUG: string
  readonly VITE_APP_URL: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}

// Node.js types for timeout handling in browser environment
declare global {
  namespace NodeJS {
    type Timeout = number;
  }
} 