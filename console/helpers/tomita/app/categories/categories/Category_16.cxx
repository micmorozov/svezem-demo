#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 16 - Крупногабаритные перевозки

/*
Перевезти крупногабаритный груз
*/

//Transp -> AnyWord<kwtype="перевезти">;
What -> "крупногабаритный" | "тяжеловесный" | "тяжелый" | "большой";

//Fact -> Transp (AnyWord+) What;
Fact -> What;

S -> Fact interp(Category.Category_16);
