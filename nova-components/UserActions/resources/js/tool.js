import UserActionsCard from './components/UserActionsCard.vue'

Nova.booting((app, store) => {
    app.component('user-actions', UserActionsCard)
})
