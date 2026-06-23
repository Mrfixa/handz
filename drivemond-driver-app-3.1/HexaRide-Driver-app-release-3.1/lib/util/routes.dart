/// Route names for navigation throughout the driver app.
/// Using constants provides better maintainability and enables
/// future deep linking support.
abstract class Routes {
  // Splash & Maintenance
  static const String splash = '/splash';
  static const String maintenance = '/maintenance';

  // Auth
  static const String signIn = '/auth/sign-in';
  static const String signUp = '/auth/sign-up';
  static const String tokenGate = '/auth/token-gate';
  static const String qrScanner = '/auth/qr-scanner';
  static const String additionalSignUp1 = '/auth/sign-up/step-1';
  static const String additionalSignUp2 = '/auth/sign-up/step-2';
  static const String forgotPassword = '/auth/forgot-password';
  static const String resetPassword = '/auth/reset-password';
  static const String changePin = '/auth/change-pin';
  static const String verification = '/auth/verification';

  // Main
  static const String dashboard = '/dashboard';
  static const String map = '/map';

  // Ride
  static const String rideRequestList = '/ride/request-list';
  static const String trip = '/trip';
  static const String tripDetails = '/trip/details';
  static const String paymentReceived = '/trip/payment-received';
  static const String reviewCustomer = '/trip/review-customer';

  // Mart
  static const String martPendingOrders = '/mart/pending';
  static const String martOrderHistory = '/mart/order-history';
  static const String martDelivery = '/mart/delivery';
  static const String martMessage = '/mart/message';

  // Wallet
  static const String wallet = '/wallet';
  static const String paymentInfo = '/wallet/payment-info';
  static const String addPaymentInfo = '/wallet/add-payment';
  static const String bankInfoEdit = '/wallet/bank-edit';
  static const String updatePaymentInfo = '/wallet/payment-update';
  static const String payableHistory = '/wallet/payable-history';
  static const String digitalPayment = '/wallet/digital-payment';

  // Profile
  static const String profile = '/profile';
  static const String profileMenu = '/profile/menu';
  static const String editProfile = '/profile/edit';
  static const String faceVerification = '/profile/face-verification';
  static const String faceVerificationResult = '/profile/face-verification/result';

  // Support & Settings
  static const String settings = '/settings';
  static const String helpAndSupport = '/support';
  static const String supportChat = '/support/chat';
  static const String notification = '/notifications';
  static const String leaderboard = '/leaderboard';
  static const String liveLocation = '/live-location';
  static const String review = '/review';
  static const String referAndEarn = '/refer';
  static const String referralDetails = '/refer/details';
  static const String referralEarnings = '/refer/earnings';
}
