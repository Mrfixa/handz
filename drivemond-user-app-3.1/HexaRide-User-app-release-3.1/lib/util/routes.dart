/// Route names for navigation throughout the app.
/// Using constants provides better maintainability and enables
/// future deep linking support.
abstract class Routes {
  // Splash & Onboarding
  static const String splash = '/splash';
  static const String onboarding = '/onboarding';

  // Auth
  static const String signIn = '/auth/sign-in';
  static const String signUp = '/auth/sign-up';
  static const String tokenGate = '/auth/token-gate';
  static const String qrScanner = '/auth/qr-scanner';
  static const String otpSignUp = '/auth/otp-signup';
  static const String otpLogin = '/auth/otp-login';
  static const String forgotPassword = '/auth/forgot-password';
  static const String resetPassword = '/auth/reset-password';
  static const String changePin = '/auth/change-pin';
  static const String verification = '/auth/verification';

  // Main
  static const String dashboard = '/dashboard';
  static const String map = '/map';
  static const String setDestination = '/set-destination';

  // Ride
  static const String rideList = '/ride/list';
  static const String trip = '/trip';
  static const String tripDetails = '/trip/details';
  static const String scheduleTrip = '/trip/schedule';

  // Parcel
  static const String parcel = '/parcel';
  static const String parcelList = '/parcel/list';

  // Mart
  static const String martStore = '/mart/store';
  static const String martProductDetails = '/mart/product';
  static const String martOrderHistory = '/mart/order-history';
  static const String martOrderTracking = '/mart/order-tracking';
  static const String martPayment = '/mart/payment';
  static const String martMessage = '/mart/message';

  // Wallet
  static const String wallet = '/wallet';
  static const String addFunds = '/wallet/add-funds';
  static const String loyaltyPoints = '/wallet/loyalty';
  static const String payment = '/payment';
  static const String reviewScreen = '/payment/review';
  static const String digitalPayment = '/payment/digital';

  // Profile & Settings
  static const String profile = '/profile';
  static const String editProfile = '/profile/edit';
  static const String settings = '/settings';
  static const String policy = '/settings/policy';
  static const String safetySetup = '/safety/setup';
  static const String referAndEarn = '/refer';
  static const String referralDetails = '/refer/details';
  static const String referralEarnings = '/refer/earnings';

  // Support
  static const String support = '/support';
  static const String notification = '/notifications';
  static const String message = '/messages';
  static const String liveLocation = '/live-location';
  static const String refundRequest = '/refund';

  // My Level
  static const String myLevel = '/my-level';
}
