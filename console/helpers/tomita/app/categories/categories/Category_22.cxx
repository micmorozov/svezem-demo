#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 22 - Перевозка 5 тонн

Numeral -> AnyWord<wff=/5([\s-]?(и|ти))?/>;
NumeralWord -> "пять";
Number -> Numeral | NumeralWord;

Tonn -> "т"|"тн"|"тон"|"тонна"|"тонный"|"тонник";

Phrase -> Number Tonn;
OneWord -> "пятитонный" | "пятитонник";

//Частный случай
//Т.к часто пишут 5т без пробелов
SpecialCase -> AnyWord<wff=/5-?(т|тн|тон(н|ны|ный|ник)?)/>;

Fact -> Phrase | SpecialCase | OneWord;

S -> Fact interp(Category.Category_22);
