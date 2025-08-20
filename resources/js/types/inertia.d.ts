import { FormOptions } from '@inertiajs/core'

declare module '@inertiajs/core' {
  interface FormOptions {
    transform?: () => FormData
  }
}