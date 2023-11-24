#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 21 - Перевозка 3 тонны

Numeral -> AnyWord<wff=/3([\s-]?х)?/>;
NumeralWord -> "три";
Number -> Numeral | NumeralWord;

Tonn -> "т"|"тн"|"тон"|"тонна"|"тонный"|"тонник";

Phrase -> Number Tonn;
OneWord -> "трехтонный" | "трехтонник";

//Частный случай
//Т.к часто пишут 3т без пробелов
SpecialCase -> AnyWord<wff=/3-?(т|тн|тон(н|ны|ный|ник)?)/>;

Fact -> Phrase | SpecialCase | OneWord;

S -> Fact interp(Category.Category_21);

