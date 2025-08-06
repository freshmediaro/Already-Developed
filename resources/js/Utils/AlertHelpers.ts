// Alert Helper Functions - Common alert scenarios
import { alertService } from '../Core/AlertService';

/**
 * Show logout confirmation dialog
 *
 * This function displays a confirmation dialog asking the user if they
 * want to log out of the system.
 *
 * @returns {Promise<boolean>} Promise that resolves to true if user confirms, false if cancelled
 */
export function showLogoutConfirmation(): Promise<boolean> {
  return alertService.showConfirmDialog({
    title: "Log out?",
    message: "Are you sure you want to log out?",
    iconClass: "fa-sign-out-alt",
    okText: "Log out",
    cancelText: "Cancel",
    style: "logout"
  });
}

/**
 * Show app uninstall confirmation dialog
 *
 * This function displays a confirmation dialog asking the user if they
 * want to uninstall a specific application.
 *
 * @param {string} appTitle The title of the app to be uninstalled
 * @returns {Promise<boolean>} Promise that resolves to true if user confirms, false if cancelled
 */
export function showUninstallConfirmation(appTitle: string): Promise<boolean> {
  return alertService.showConfirmDialog({
    title: 'Uninstall App',
    message: `Are you sure you want to uninstall <b>${appTitle}</b>? This cannot be undone!`,
    iconClass: 'fa-trash',
    okText: 'Uninstall',
    cancelText: 'Cancel',
    style: 'desktop'
  });
}

/**
 * Show order status change confirmation with options
 *
 * This function displays a confirmation dialog for changing order status
 * with additional options for email notifications and answer remembering.
 *
 * @param {string} newStatus The new status to change the order to
 * @returns {Promise<boolean>} Promise that resolves to true if user confirms, false if cancelled
 */
export function showOrderStatusConfirmation(newStatus: string): Promise<boolean> {
  return alertService.showConfirmDialog({
    title: `Change status to ${newStatus}`,
    message: `Are you sure you want to change order status to ${newStatus}?`,
    iconClass: 'fa-repeat',
    okText: 'Confirm',
    cancelText: 'Cancel',
    style: 'desktop',
    showCheckboxes: true,
    checkboxes: [
      { id: 'send-email-checkbox', label: 'Send email to customer' },
      { id: 'remember-answer-checkbox', label: "Remember answer and don't ask me again" }
    ]
  });
}

/**
 * Common system feedback notifications
 *
 * This object contains predefined notification functions for common
 * system feedback scenarios, providing consistent user feedback
 * across the application.
 *
 * @type {Object}
 */
export const SystemNotifications = {
  // App management
  pinnedToTaskbar: () => alertService.showShortTopNotification('Pinned to Taskbar'),
  unpinnedFromTaskbar: () => alertService.showShortTopNotification('Unpinned from Taskbar'),
  appUninstalled: () => alertService.showShortTopNotification('App uninstalled'),
  addedToDesktop: () => alertService.showShortTopNotification('Added to Desktop'),
  removedFromDesktop: () => alertService.showShortTopNotification('Removed from Desktop'),

  // Desktop sorting
  sortedByName: () => alertService.showShortTopNotification('Sorted by name'),
  sortedByDate: () => alertService.showShortTopNotification('Sorted by date'),
  sortedByType: () => alertService.showShortTopNotification('Sorted by type (apps first)'),

  // Taskbar customization
  taskbarStyleDefault: () => alertService.showShortTopNotification('Taskbar style set to Default'),
  taskbarStyleWindows11: () => alertService.showShortTopNotification('Taskbar style set to Windows 11'),
  taskbarIconsLeft: () => alertService.showShortTopNotification('Taskbar icons aligned to the left'),
  taskbarIconsAndText: () => alertService.showShortTopNotification('Taskbar icons and text mode enabled'),

  // System tray visibility
  appLauncherShown: () => alertService.showShortTopNotification('App Launcher shown'),
  appLauncherHidden: () => alertService.showShortTopNotification('App Launcher hidden'),
  searchIconShown: () => alertService.showShortTopNotification('Search icon shown'),
  searchIconHidden: () => alertService.showShortTopNotification('Search icon hidden'),
  volumeButtonShown: () => alertService.showShortTopNotification('Volume button shown'),
  volumeButtonHidden: () => alertService.showShortTopNotification('Volume button hidden'),
  walletButtonShown: () => alertService.showShortTopNotification('Wallet button shown'),
  walletButtonHidden: () => alertService.showShortTopNotification('Wallet button hidden'),
  fullscreenButtonShown: () => alertService.showShortTopNotification('Full screen button shown'),
  fullscreenButtonHidden: () => alertService.showShortTopNotification('Full screen button hidden'),

  // Window management
  allWindowsMinimized: () => alertService.showShortTopNotification('All windows minimized'),

  // Notification settings
  notificationsOnly1: () => alertService.showShortTopNotification('Desktop notifications: Only 1'),
  notificationsOnly3: () => alertService.showShortTopNotification('Desktop notifications: Only 3'),
  notificationsAll: () => alertService.showShortTopNotification('Desktop notifications: All'),

  // Fullscreen
  fullscreenInteractionRequired: () => alertService.showShortTopNotification('Fullscreen must be triggered by user interaction'),
  fullscreenRequestFailed: () => alertService.showShortTopNotification('Fullscreen request failed'),

  // General errors
  desktopNotFound: () => alertService.showShortTopNotification('Desktop not found'),
  alreadyOnDesktop: () => alertService.showShortTopNotification('Already on Desktop'),

   // Clipboard feedback (already handled in ClipboardService)
   textCut: () => alertService.showShortTopNotification('Text cut to clipboard'),
   textCopied: () => alertService.showShortTopNotification('Text copied to clipboard'),
   textPasted: () => alertService.showShortTopNotification('Text pasted from clipboard'),
   allTextSelected: () => alertService.showShortTopNotification('All text selected'),
   cannotCutNonEditable: () => alertService.showShortTopNotification('Cannot cut/delete non-editable text'),
   cannotPasteNonEditable: () => alertService.showShortTopNotification('Cannot paste into non-editable text'),

  // Team management
  teamSwitched: (teamName: string) => alertService.showShortTopNotification(`Switched to ${teamName}`),
  teamCreated: (teamName: string) => alertService.showShortTopNotification(`Team ${teamName} created`),
  teamDeleted: (teamName: string) => alertService.showShortTopNotification(`Team ${teamName} deleted`),

  // File operations
  fileUploaded: (fileName: string) => alertService.showShortTopNotification(`${fileName} uploaded`),
  fileDeleted: (fileName: string) => alertService.showShortTopNotification(`${fileName} deleted`),
  folderCreated: (folderName: string) => alertService.showShortTopNotification(`Folder ${folderName} created`),

  // Settings
  settingsSaved: () => alertService.showShortTopNotification('Settings saved'),
  settingsReset: () => alertService.showShortTopNotification('Settings reset to defaults'),

  // AI Chat
  aiChatStarted: () => alertService.showShortTopNotification('AI Chat started'),
  aiChatStopped: () => alertService.showShortTopNotification('AI Chat stopped'),
  aiTokensPurchased: (amount: number) => alertService.showShortTopNotification(`${amount} AI tokens purchased`),

  // Wallet
  walletToppedUp: (amount: number) => alertService.showShortTopNotification(`Wallet topped up with $${amount}`),
  paymentProcessed: (amount: number) => alertService.showShortTopNotification(`Payment processed: $${amount}`),
  withdrawalRequested: (amount: number) => alertService.showShortTopNotification(`Withdrawal requested: $${amount}`),

  // App Store
  appInstalled: (appName: string) => alertService.showShortTopNotification(`${appName} installed`),
  appUpdated: (appName: string) => alertService.showShortTopNotification(`${appName} updated`),
  appPurchased: (appName: string) => alertService.showShortTopNotification(`${appName} purchased`),

  // Notifications
  notificationsEnabled: () => alertService.showShortTopNotification('Notifications enabled'),
  notificationsDisabled: () => alertService.showShortTopNotification('Notifications disabled'),
  doNotDisturbEnabled: () => alertService.showShortTopNotification('Do not disturb enabled'),
  doNotDisturbDisabled: () => alertService.showShortTopNotification('Do not disturb disabled'),

  // System
  systemUpdateAvailable: () => alertService.showShortTopNotification('System update available'),
  systemUpdateInstalled: () => alertService.showShortTopNotification('System update installed'),
  backupCreated: () => alertService.showShortTopNotification('Backup created'),
  backupRestored: () => alertService.showShortTopNotification('Backup restored'),

  // Security
  securityScanCompleted: () => alertService.showShortTopNotification('Security scan completed'),
  securityScanFailed: () => alertService.showShortTopNotification('Security scan failed'),
  loginDetected: (location: string) => alertService.showShortTopNotification(`Login detected from ${location}`),
  suspiciousActivity: () => alertService.showShortTopNotification('Suspicious activity detected'),

  // Performance
  performanceOptimized: () => alertService.showShortTopNotification('Performance optimized'),
  cacheCleared: () => alertService.showShortTopNotification('Cache cleared'),
  memoryFreed: () => alertService.showShortTopNotification('Memory freed'),

  // Connectivity
  connectionRestored: () => alertService.showShortTopNotification('Connection restored'),
  connectionLost: () => alertService.showShortTopNotification('Connection lost'),
  syncCompleted: () => alertService.showShortTopNotification('Sync completed'),
  syncFailed: () => alertService.showShortTopNotification('Sync failed'),

  // Updates
  updateAvailable: (appName: string) => alertService.showShortTopNotification(`Update available for ${appName}`),
  updateInstalled: (appName: string) => alertService.showShortTopNotification(`${appName} updated`),
  updateFailed: (appName: string) => alertService.showShortTopNotification(`Update failed for ${appName}`),

  // Permissions
  permissionGranted: (permission: string) => alertService.showShortTopNotification(`${permission} permission granted`),
  permissionDenied: (permission: string) => alertService.showShortTopNotification(`${permission} permission denied`),
  permissionRequested: (permission: string) => alertService.showShortTopNotification(`${permission} permission requested`),

  // Time
  timezoneChanged: (timezone: string) => alertService.showShortTopNotification(`Timezone changed to ${timezone}`),
  timeFormatChanged: (format: string) => alertService.showShortTopNotification(`Time format changed to ${format}`),
  dateFormatChanged: (format: string) => alertService.showShortTopNotification(`Date format changed to ${format}`),

  // Language
  languageChanged: (language: string) => alertService.showShortTopNotification(`Language changed to ${language}`),
  translationUpdated: () => alertService.showShortTopNotification('Translation updated'),

  // Accessibility
  accessibilityEnabled: () => alertService.showShortTopNotification('Accessibility features enabled'),
  accessibilityDisabled: () => alertService.showShortTopNotification('Accessibility features disabled'),
  highContrastEnabled: () => alertService.showShortTopNotification('High contrast enabled'),
  highContrastDisabled: () => alertService.showShortTopNotification('High contrast disabled'),

  // Privacy
  privacyModeEnabled: () => alertService.showShortTopNotification('Privacy mode enabled'),
  privacyModeDisabled: () => alertService.showShortTopNotification('Privacy mode disabled'),
  trackingDisabled: () => alertService.showShortTopNotification('Tracking disabled'),
  trackingEnabled: () => alertService.showShortTopNotification('Tracking enabled'),

  // Development
  developerModeEnabled: () => alertService.showShortTopNotification('Developer mode enabled'),
  developerModeDisabled: () => alertService.showShortTopNotification('Developer mode disabled'),
  debugModeEnabled: () => alertService.showShortTopNotification('Debug mode enabled'),
  debugModeDisabled: () => alertService.showShortTopNotification('Debug mode disabled'),

  // Maintenance
  maintenanceModeEnabled: () => alertService.showShortTopNotification('Maintenance mode enabled'),
  maintenanceModeDisabled: () => alertService.showShortTopNotification('Maintenance mode disabled'),
  systemRebooted: () => alertService.showShortTopNotification('System rebooted'),
  systemShutdown: () => alertService.showShortTopNotification('System shutdown'),

  // Network
  networkConnected: () => alertService.showShortTopNotification('Network connected'),
  networkDisconnected: () => alertService.showShortTopNotification('Network disconnected'),
  wifiConnected: (ssid: string) => alertService.showShortTopNotification(`Connected to ${ssid}`),
  wifiDisconnected: () => alertService.showShortTopNotification('WiFi disconnected'),

  // Storage
  storageLow: () => alertService.showShortTopNotification('Storage space low'),
  storageFreed: (amount: string) => alertService.showShortTopNotification(`${amount} of storage freed`),
  backupStorageFull: () => alertService.showShortTopNotification('Backup storage full'),

  // Battery
  batteryLow: () => alertService.showShortTopNotification('Battery low'),
  batteryCharging: () => alertService.showShortTopNotification('Battery charging'),
  batteryFull: () => alertService.showShortTopNotification('Battery fully charged'),

  // Temperature
  systemOverheating: () => alertService.showShortTopNotification('System overheating'),
  temperatureNormal: () => alertService.showShortTopNotification('Temperature normal'),

  // Hardware
  deviceConnected: (deviceName: string) => alertService.showShortTopNotification(`${deviceName} connected`),
  deviceDisconnected: (deviceName: string) => alertService.showShortTopNotification(`${deviceName} disconnected`),
  deviceDriverUpdated: (deviceName: string) => alertService.showShortTopNotification(`${deviceName} driver updated`),

  // Software
  softwareInstalled: (softwareName: string) => alertService.showShortTopNotification(`${softwareName} installed`),
  softwareUninstalled: (softwareName: string) => alertService.showShortTopNotification(`${softwareName} uninstalled`),
  softwareUpdated: (softwareName: string) => alertService.showShortTopNotification(`${softwareName} updated`),

  // Security
  firewallEnabled: () => alertService.showShortTopNotification('Firewall enabled'),
  firewallDisabled: () => alertService.showShortTopNotification('Firewall disabled'),
  antivirusUpdated: () => alertService.showShortTopNotification('Antivirus updated'),
  malwareDetected: () => alertService.showShortTopNotification('Malware detected'),

  // User Management
  userLoggedIn: (username: string) => alertService.showShortTopNotification(`${username} logged in`),
  userLoggedOut: (username: string) => alertService.showShortTopNotification(`${username} logged out`),
  userCreated: (username: string) => alertService.showShortTopNotification(`User ${username} created`),
  userDeleted: (username: string) => alertService.showShortTopNotification(`User ${username} deleted`),

  // Data
  dataExported: (format: string) => alertService.showShortTopNotification(`Data exported to ${format}`),
  dataImported: (format: string) => alertService.showShortTopNotification(`Data imported from ${format}`),
  dataBackedUp: () => alertService.showShortTopNotification('Data backed up'),
  dataRestored: () => alertService.showShortTopNotification('Data restored'),

  // API
  apiConnected: () => alertService.showShortTopNotification('API connected'),
  apiDisconnected: () => alertService.showShortTopNotification('API disconnected'),
  apiRateLimited: () => alertService.showShortTopNotification('API rate limited'),

  // Webhooks
  webhookReceived: (source: string) => alertService.showShortTopNotification(`Webhook received from ${source}`),
  webhookFailed: (source: string) => alertService.showShortTopNotification(`Webhook failed from ${source}`),
  webhookProcessed: (source: string) => alertService.showShortTopNotification(`Webhook processed from ${source}`),

  // Integrations
  integrationConnected: (service: string) => alertService.showShortTopNotification(`${service} integration connected`),
  integrationDisconnected: (service: string) => alertService.showShortTopNotification(`${service} integration disconnected`),
  integrationSynced: (service: string) => alertService.showShortTopNotification(`${service} integration synced`),

  // Compliance
  complianceCheckPassed: () => alertService.showShortTopNotification('Compliance check passed'),
  complianceCheckFailed: () => alertService.showShortTopNotification('Compliance check failed'),
  auditLogGenerated: () => alertService.showShortTopNotification('Audit log generated'),

  // Monitoring
  monitoringEnabled: () => alertService.showShortTopNotification('Monitoring enabled'),
  monitoringDisabled: () => alertService.showShortTopNotification('Monitoring disabled'),
  alertTriggered: (alertName: string) => alertService.showShortTopNotification(`Alert triggered: ${alertName}`),

  // Automation
  automationStarted: (automationName: string) => alertService.showShortTopNotification(`Automation started: ${automationName}`),
  automationCompleted: (automationName: string) => alertService.showShortTopNotification(`Automation completed: ${automationName}`),
  automationFailed: (automationName: string) => alertService.showShortTopNotification(`Automation failed: ${automationName}`),

  // Workflow
  workflowStarted: (workflowName: string) => alertService.showShortTopNotification(`Workflow started: ${workflowName}`),
  workflowCompleted: (workflowName: string) => alertService.showShortTopNotification(`Workflow completed: ${workflowName}`),
  workflowFailed: (workflowName: string) => alertService.showShortTopNotification(`Workflow failed: ${workflowName}`),

  // Collaboration
  collaborationStarted: (projectName: string) => alertService.showShortTopNotification(`Collaboration started: ${projectName}`),
  collaborationEnded: (projectName: string) => alertService.showShortTopNotification(`Collaboration ended: ${projectName}`),
  documentShared: (documentName: string) => alertService.showShortTopNotification(`Document shared: ${documentName}`),

  // Communication
  messageReceived: (sender: string) => alertService.showShortTopNotification(`Message from ${sender}`),
  messageSent: (recipient: string) => alertService.showShortTopNotification(`Message sent to ${recipient}`),
  callStarted: (contact: string) => alertService.showShortTopNotification(`Call started with ${contact}`),
  callEnded: (contact: string) => alertService.showShortTopNotification(`Call ended with ${contact}`),

  // Calendar
  eventCreated: (eventName: string) => alertService.showShortTopNotification(`Event created: ${eventName}`),
  eventUpdated: (eventName: string) => alertService.showShortTopNotification(`Event updated: ${eventName}`),
  eventReminded: (eventName: string) => alertService.showShortTopNotification(`Event reminder: ${eventName}`),

  // Tasks
  taskCreated: (taskName: string) => alertService.showShortTopNotification(`Task created: ${taskName}`),
  taskCompleted: (taskName: string) => alertService.showShortTopNotification(`Task completed: ${taskName}`),
  taskOverdue: (taskName: string) => alertService.showShortTopNotification(`Task overdue: ${taskName}`),

  // Projects
  projectCreated: (projectName: string) => alertService.showShortTopNotification(`Project created: ${projectName}`),
  projectCompleted: (projectName: string) => alertService.showShortTopNotification(`Project completed: ${projectName}`),
  projectMilestone: (projectName: string, milestone: string) => alertService.showShortTopNotification(`${projectName}: ${milestone} milestone reached`),

  // Reports
  reportGenerated: (reportName: string) => alertService.showShortTopNotification(`Report generated: ${reportName}`),
  reportScheduled: (reportName: string) => alertService.showShortTopNotification(`Report scheduled: ${reportName}`),
  reportFailed: (reportName: string) => alertService.showShortTopNotification(`Report failed: ${reportName}`),

  // Analytics
  analyticsUpdated: () => alertService.showShortTopNotification('Analytics updated'),
  insightsGenerated: () => alertService.showShortTopNotification('Insights generated'),
  trendsDetected: () => alertService.showShortTopNotification('Trends detected'),

  // Machine Learning
  modelTrained: (modelName: string) => alertService.showShortTopNotification(`Model trained: ${modelName}`),
  predictionGenerated: (modelName: string) => alertService.showShortTopNotification(`Prediction generated: ${modelName}`),
  accuracyImproved: (modelName: string) => alertService.showShortTopNotification(`Accuracy improved: ${modelName}`),

  // Blockchain
  transactionConfirmed: (txHash: string) => alertService.showShortTopNotification(`Transaction confirmed: ${txHash}`),
  smartContractDeployed: (contractName: string) => alertService.showShortTopNotification(`Smart contract deployed: ${contractName}`),
  walletConnected: (walletName: string) => alertService.showShortTopNotification(`Wallet connected: ${walletName}`),

  // IoT
  deviceOnline: (deviceName: string) => alertService.showShortTopNotification(`${deviceName} online`),
  deviceOffline: (deviceName: string) => alertService.showShortTopNotification(`${deviceName} offline`),
  sensorReading: (sensorName: string, value: string) => alertService.showShortTopNotification(`${sensorName}: ${value}`),

  // AR/VR
  arSessionStarted: () => alertService.showShortTopNotification('AR session started'),
  vrSessionStarted: () => alertService.showShortTopNotification('VR session started'),
  spatialMapping: () => alertService.showShortTopNotification('Spatial mapping completed'),

  // Quantum
  quantumJobSubmitted: (jobName: string) => alertService.showShortTopNotification(`Quantum job submitted: ${jobName}`),
  quantumJobCompleted: (jobName: string) => alertService.showShortTopNotification(`Quantum job completed: ${jobName}`),
  quantumError: (errorType: string) => alertService.showShortTopNotification(`Quantum error: ${errorType}`),

  // Edge Computing
  edgeNodeConnected: (nodeName: string) => alertService.showShortTopNotification(`Edge node connected: ${nodeName}`),
  edgeProcessing: (taskName: string) => alertService.showShortTopNotification(`Edge processing: ${taskName}`),
  edgeSync: (nodeName: string) => alertService.showShortTopNotification(`Edge sync: ${nodeName}`),

  // 5G/6G
  networkUpgraded: (generation: string) => alertService.showShortTopNotification(`Network upgraded to ${generation}`),
  latencyReduced: (reduction: string) => alertService.showShortTopNotification(`Latency reduced by ${reduction}`),
  bandwidthIncreased: (increase: string) => alertService.showShortTopNotification(`Bandwidth increased by ${increase}`),

  // Sustainability
  energyOptimized: () => alertService.showShortTopNotification('Energy consumption optimized'),
  carbonReduced: (reduction: string) => alertService.showShortTopNotification(`Carbon footprint reduced by ${reduction}`),
  renewableEnergy: () => alertService.showShortTopNotification('Switched to renewable energy'),

  // Accessibility
  voiceControlEnabled: () => alertService.showShortTopNotification('Voice control enabled'),
  gestureControlEnabled: () => alertService.showShortTopNotification('Gesture control enabled'),
  eyeTrackingEnabled: () => alertService.showShortTopNotification('Eye tracking enabled'),

  // Health
  healthCheckPassed: () => alertService.showShortTopNotification('Health check passed'),
  healthCheckFailed: () => alertService.showShortTopNotification('Health check failed'),
  wellnessReminder: () => alertService.showShortTopNotification('Wellness reminder'),

  // Education
  courseEnrolled: (courseName: string) => alertService.showShortTopNotification(`Enrolled in ${courseName}`),
  lessonCompleted: (lessonName: string) => alertService.showShortTopNotification(`Lesson completed: ${lessonName}`),
  certificateEarned: (certificateName: string) => alertService.showShortTopNotification(`Certificate earned: ${certificateName}`),

  // Entertainment
  mediaPlayed: (mediaName: string) => alertService.showShortTopNotification(`Now playing: ${mediaName}`),
  playlistCreated: (playlistName: string) => alertService.showShortTopNotification(`Playlist created: ${playlistName}`),
  favoriteAdded: (itemName: string) => alertService.showShortTopNotification(`Added to favorites: ${itemName}`),

  // Gaming
  gameStarted: (gameName: string) => alertService.showShortTopNotification(`Game started: ${gameName}`),
  achievementUnlocked: (achievementName: string) => alertService.showShortTopNotification(`Achievement unlocked: ${achievementName}`),
  leaderboardUpdated: (gameName: string) => alertService.showShortTopNotification(`Leaderboard updated: ${gameName}`),

  // Social
  friendRequest: (friendName: string) => alertService.showShortTopNotification(`Friend request from ${friendName}`),
  postShared: (platform: string) => alertService.showShortTopNotification(`Post shared on ${platform}`),
  notificationReceived: (appName: string) => alertService.showShortTopNotification(`Notification from ${appName}`),

  // Finance
  transactionProcessed: (amount: number) => alertService.showShortTopNotification(`Transaction processed: $${amount}`),
  budgetAlert: (category: string) => alertService.showShortTopNotification(`Budget alert: ${category}`),
  investmentUpdate: (portfolio: string) => alertService.showShortTopNotification(`Investment update: ${portfolio}`),

  // Travel
  tripBooked: (destination: string) => alertService.showShortTopNotification(`Trip booked to ${destination}`),
  flightDelayed: (flightNumber: string) => alertService.showShortTopNotification(`Flight delayed: ${flightNumber}`),
  hotelConfirmed: (hotelName: string) => alertService.showShortTopNotification(`Hotel confirmed: ${hotelName}`),

  // Shopping
  orderPlaced: (orderNumber: string) => alertService.showShortTopNotification(`Order placed: ${orderNumber}`),
  orderShipped: (orderNumber: string) => alertService.showShortTopNotification(`Order shipped: ${orderNumber}`),
  orderDelivered: (orderNumber: string) => alertService.showShortTopNotification(`Order delivered: ${orderNumber}`),

  // Food
  mealPlanned: (mealName: string) => alertService.showShortTopNotification(`Meal planned: ${mealName}`),
  recipeSaved: (recipeName: string) => alertService.showShortTopNotification(`Recipe saved: ${recipeName}`),
  nutritionTracked: (calories: number) => alertService.showShortTopNotification(`Nutrition tracked: ${calories} calories`),

  // Fitness
  workoutStarted: (workoutType: string) => alertService.showShortTopNotification(`Workout started: ${workoutType}`),
  workoutCompleted: (duration: string) => alertService.showShortTopNotification(`Workout completed: ${duration}`),
  goalAchieved: (goalName: string) => alertService.showShortTopNotification(`Goal achieved: ${goalName}`),

  // Home
  deviceConnected2: (deviceName: string) => alertService.showShortTopNotification(`${deviceName} connected`),
  automationTriggered: (automationName: string) => alertService.showShortTopNotification(`Automation triggered: ${automationName}`),
  energySaved: (savings: string) => alertService.showShortTopNotification(`Energy saved: ${savings}`),

  // Work
  meetingScheduled: (meetingName: string) => alertService.showShortTopNotification(`Meeting scheduled: ${meetingName}`),
  deadlineApproaching: (taskName: string) => alertService.showShortTopNotification(`Deadline approaching: ${taskName}`),
  projectUpdate: (projectName: string) => alertService.showShortTopNotification(`Project update: ${projectName}`),

  // Learning
  skillLearned: (skillName: string) => alertService.showShortTopNotification(`Skill learned: ${skillName}`),
  quizCompleted: (quizName: string) => alertService.showShortTopNotification(`Quiz completed: ${quizName}`),
  progressTracked: (subject: string) => alertService.showShortTopNotification(`Progress tracked: ${subject}`),

  // Creativity
  artworkCreated: (artworkName: string) => alertService.showShortTopNotification(`Artwork created: ${artworkName}`),
  musicComposed: (songName: string) => alertService.showShortTopNotification(`Music composed: ${songName}`),
  storyWritten: (storyName: string) => alertService.showShortTopNotification(`Story written: ${storyName}`),

  // Research
  experimentStarted: (experimentName: string) => alertService.showShortTopNotification(`Experiment started: ${experimentName}`),
  dataAnalyzed: (datasetName: string) => alertService.showShortTopNotification(`Data analyzed: ${datasetName}`),
  hypothesisTested: (hypothesisName: string) => alertService.showShortTopNotification(`Hypothesis tested: ${hypothesisName}`),

  // Innovation
  patentFiled: (inventionName: string) => alertService.showShortTopNotification(`Patent filed: ${inventionName}`),
  prototypeBuilt: (prototypeName: string) => alertService.showShortTopNotification(`Prototype built: ${prototypeName}`),
  breakthroughAchieved: (breakthroughName: string) => alertService.showShortTopNotification(`Breakthrough achieved: ${breakthroughName}`),

  // Legacy
  systemRetired: (systemName: string) => alertService.showShortTopNotification(`System retired: ${systemName}`),
  migrationCompleted: (fromSystem: string, toSystem: string) => alertService.showShortTopNotification(`Migration completed: ${fromSystem} to ${toSystem}`),
  archiveCreated: (archiveName: string) => alertService.showShortTopNotification(`Archive created: ${archiveName}`),

  // Future
  timeTravelInitiated: (destination: string) => alertService.showShortTopNotification(`Time travel initiated: ${destination}`),
  dimensionOpened: (dimensionName: string) => alertService.showShortTopNotification(`Dimension opened: ${dimensionName}`),
  realityShifted: (shiftType: string) => alertService.showShortTopNotification(`Reality shifted: ${shiftType}`)
};

/**
 * Show system toast notification
 *
 * This function displays a system toast notification with customizable
 * title, description, icon, and styling options.
 *
 * @param {Object} options Toast notification options
 * @param {string} options.title The title of the toast notification
 * @param {string} options.description The description of the toast notification
 * @param {string} [options.iconClass] Optional CSS class for the icon
 * @param {string} [options.iconBgClass] Optional CSS class for icon background
 * @param {string} [options.avatar] Optional avatar image URL
 */
export function showSystemToast(options: {
  title: string;
  description: string;
  iconClass?: string;
  iconBgClass?: string;
  avatar?: string;
}): void {
  alertService.showToastNotification({
    title: options.title,
    description: options.description,
    iconClass: options.iconClass || 'fa-info-circle',
    iconBgClass: options.iconBgClass || 'notif-bg-blue',
    avatar: options.avatar,
    meta: 'now'
  });
}

/**
 * Show welcome toast notification
 *
 * This function displays a welcome toast notification for new users
 * or when the system is first accessed.
 */
export function showWelcomeToast(): void {
  showSystemToast({
    title: 'Welcome to the Dashboard',
    description: 'Your new OS-style dashboard is ready to use!',
    iconClass: 'fa-rocket',
    iconBgClass: 'notif-bg-blue',
    avatar: 'img/avatar.png'
  });
}

/**
 * Show update toast notification
 *
 * This function displays an update toast notification when the system
 * has been updated or new features are available.
 */
export function showUpdateToast(): void {
  showSystemToast({
    title: 'System Update Available',
    description: 'A new system update is available for download.',
    iconClass: 'fa-download',
    iconBgClass: 'notif-bg-green',
    avatar: 'img/avatar.png'
  });
} 