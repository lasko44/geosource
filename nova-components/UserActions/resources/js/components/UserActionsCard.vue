<template>
    <div>
        <div class="flex items-center gap-6 px-6 py-4">
            <button
                @click="showConfirm('verify')"
                :disabled="loading || isVerified"
                class="shadow relative bg-primary-500 text-white dark:text-gray-900 cursor-pointer rounded text-sm font-bold focus:outline-none focus:ring ring-primary-200 dark:ring-gray-600 inline-flex items-center justify-center h-9 px-3 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <svg v-if="loading === 'verify'" class="animate-spin mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                {{ isVerified ? 'Already Verified' : 'Verify Email' }}
            </button>

            <button
                @click="showConfirm('resend')"
                :disabled="loading || isVerified"
                class="shadow relative bg-primary-500 text-white dark:text-gray-900 cursor-pointer rounded text-sm font-bold focus:outline-none focus:ring ring-primary-200 dark:ring-gray-600 inline-flex items-center justify-center h-9 px-3 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <svg v-if="loading === 'resend'" class="animate-spin mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Resend Verification Email
            </button>

            <span v-if="message" class="text-sm ml-2" :class="messageType === 'success' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                {{ message }}
            </span>
        </div>

        <!-- Confirmation Modal -->
        <Modal :show="confirmAction !== null" @close-via-escape="confirmAction = null" role="alertdialog" size="sm">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden" style="max-width: 28rem; margin: 0 auto;">
                <ModalHeader class="flex items-center">
                    {{ confirmAction === 'verify' ? 'Verify Email' : 'Resend Verification' }}
                </ModalHeader>
                <ModalContent>
                    <p class="leading-normal">
                        {{ confirmAction === 'verify'
                            ? 'This will manually mark this user\'s email as verified. Are you sure?'
                            : 'This will resend the email verification notification to the user. Continue?' }}
                    </p>
                </ModalContent>
                <ModalFooter>
                    <div class="flex items-center ml-auto">
                        <button
                            type="button"
                            @click="confirmAction = null"
                            class="shadow relative bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-400 cursor-pointer rounded text-sm font-bold focus:outline-none focus:ring ring-primary-200 dark:ring-gray-600 inline-flex items-center justify-center h-9 px-3 mr-3"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            @click="executeAction"
                            :disabled="loading"
                            class="shadow relative bg-primary-500 text-white dark:text-gray-900 cursor-pointer rounded text-sm font-bold focus:outline-none focus:ring ring-primary-200 dark:ring-gray-600 inline-flex items-center justify-center h-9 px-3"
                        >
                            {{ confirmAction === 'verify' ? 'Verify' : 'Send Email' }}
                        </button>
                    </div>
                </ModalFooter>
            </div>
        </Modal>
    </div>
</template>

<script>
export default {
    props: ['resourceName', 'resourceId', 'field'],

    data() {
        return {
            loading: false,
            message: '',
            messageType: 'success',
            isVerified: false,
            confirmAction: null,
        }
    },

    mounted() {
        this.isVerified = this.field?.extraAttributes?.isVerified ?? false
    },

    methods: {
        showConfirm(action) {
            this.message = ''
            this.confirmAction = action
        },

        executeAction() {
            const action = this.confirmAction
            this.confirmAction = null

            if (action === 'verify') {
                this.verifyEmail()
            } else {
                this.resendVerification()
            }
        },

        async verifyEmail() {
            this.loading = 'verify'
            this.message = ''

            try {
                const response = await fetch(`/nova-vendor/user-actions/verify-email/${this.resourceId}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                })

                const data = await response.json()
                this.message = data.message || (response.ok ? 'Email verified.' : 'Failed to verify email.')
                this.messageType = response.ok ? 'success' : 'error'

                if (response.ok) {
                    this.isVerified = true
                }
            } catch (e) {
                this.message = 'Failed to verify email.'
                this.messageType = 'error'
            } finally {
                this.loading = false
            }
        },

        async resendVerification() {
            this.loading = 'resend'
            this.message = ''

            try {
                const response = await fetch(`/nova-vendor/user-actions/resend-verification/${this.resourceId}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                })

                const data = await response.json()
                this.message = data.message || (response.ok ? 'Verification email sent.' : 'Failed to send email.')
                this.messageType = response.ok ? 'success' : 'error'
            } catch (e) {
                this.message = 'Failed to send verification email.'
                this.messageType = 'error'
            } finally {
                this.loading = false
            }
        },
    },
}
</script>
